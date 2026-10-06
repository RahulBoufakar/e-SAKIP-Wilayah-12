<?php

namespace App\Http\Controllers\TimKerja\RabGenerator;

use App\Http\Controllers\Controller;
use App\Models\FileExcel;
use App\Models\Sheet;
use App\Services\RabPreviewRenderer;
use App\Services\RabReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * RAB Generator 02-arsitektur.md — alur preview dan unduh.
 * Seluruh endpoint berada di belakang config('rab_generator.enabled') (08).
 */
class RabReportController extends Controller
{
    private const CACHE_MENIT = 20;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless(config('rab_generator.enabled'), 404);

            return $next($request);
        });
    }

    // GET /tim-kerja/rab-generator/pohon — struktur file master aktif untuk pohon pilihan di modal (04 §B)
    public function pohon()
    {
        $this->authorize('generate', FileExcel::class);

        $file = FileExcel::aktif()->first();
        abort_if($file === null, 404, 'Belum ada file master RAB yang aktif. Hubungi Administrator.');

        $file->load([
            'sheets' => fn ($q) => $q->orderBy('urutan'),
            'sheets.header',
            'sheets.footer',
            'sheets.kategori' => fn ($q) => $q->orderBy('urutan'),
            'sheets.kategori.kelompok' => fn ($q) => $q->orderBy('urutan'),
        ]);

        return response()->json([
            'file_excel_id' => $file->id,
            'sheets' => $file->sheets->map(fn ($sheet) => [
                'id' => $sheet->id,
                'kode_sheet' => $sheet->kode_sheet,
                'nama_sheet' => $sheet->nama_sheet,
                'ada_header' => $sheet->header !== null,
                'ada_footer' => $sheet->footer !== null,
                'kategori' => $sheet->kategori->map(fn ($k) => [
                    'id' => $k->id,
                    'kode' => $k->kode_kategori,
                    'nama' => $k->nama_kategori,
                    'kelompok' => $k->kelompok->map(fn ($g) => [
                        'id' => $g->id,
                        'kode' => $g->kode_kelompok,
                        'label' => $g->label, // nama, atau cadangan "Sub {kode}"
                    ])->all(),
                ])->all(),
            ])->all(),
        ]);
    }

    // POST /tim-kerja/rab-generator/preview
    public function preview(Request $request, RabReportService $service, RabPreviewRenderer $renderer)
    {
        $this->authorize('generate', FileExcel::class);

        $data = $request->validate([
            'file_excel_id' => 'required|integer',
            'sheets' => 'required|array|min:1|max:50',
            'sheets.*.sheet_id' => 'required|integer|distinct',
            'sheets.*.header' => 'sometimes|boolean',
            'sheets.*.footer' => 'sometimes|boolean',
            'sheets.*.kelompok_ids' => 'required|array|min:1', // S2: sheet terpilih wajib punya kelompok
            'sheets.*.kelompok_ids.*' => 'integer|distinct',
        ], [
            'sheets.*.kelompok_ids.required' => 'Setiap sheet terpilih harus memiliki minimal satu kelompok.',
            'sheets.*.kelompok_ids.min' => 'Setiap sheet terpilih harus memiliki minimal satu kelompok.',
        ]);

        $file = FileExcel::aktif()->first();
        if (! $file) {
            throw ValidationException::withMessages(['file_excel_id' => 'Belum ada file master RAB yang aktif. Hubungi Administrator.']);
        }
        // Master diganti admin setelah modal dibuka: pilihan mengacu ke struktur yang sudah tidak berlaku.
        if ((int) $data['file_excel_id'] !== $file->id) {
            throw ValidationException::withMessages(['file_excel_id' => 'File master telah diganti. Muat ulang halaman lalu pilih kembali.']);
        }

        $pilihan = $this->susunPilihan($file, $data['sheets'], $service);

        $pathMaster = Storage::disk('private')->path($file->path);
        if (! is_file($pathMaster)) {
            throw ValidationException::withMessages(['file_excel_id' => 'File master tidak ditemukan di penyimpanan. Hubungi Administrator.']);
        }

        $hasil = $service->bangunDariFile($pathMaster, $pilihan);
        $spreadsheet = $hasil['spreadsheet'];

        try {
            $service->periksaLaporan($hasil['laporan']);
        } catch (RuntimeException $e) {
            Log::warning('RAB Generator: perakitan ditolak', ['file_excel_id' => $file->id, 'laporan' => $hasil['laporan']]);
            $spreadsheet->disconnectWorksheets();

            throw ValidationException::withMessages(['pilihan' => $e->getMessage()]);
        }

        if ($hasil['laporan']['merges_skipped'] > 0) {
            Log::warning('RAB Generator: ada merge cell yang tidak tersalin', ['file_excel_id' => $file->id, 'merges_skipped' => $hasil['laporan']['merges_skipped']]);
        }

        $token = (string) Str::uuid();
        $path = "temp/{$token}.xlsx"; // disk private, tidak pernah bisa diakses lewat URL publik
        $service->simpanXlsx($spreadsheet, Storage::disk('private')->path($path));

        Cache::put("rab_report:{$token}", [
            'user_id' => Auth::id(),
            'path' => $path,
            'nama' => 'RAB_'.now()->format('Ymd_His').'.xlsx',
            'created_at' => now()->toIso8601String(),
        ], now()->addMinutes(self::CACHE_MENIT));

        $previews = $renderer->render($spreadsheet);
        $spreadsheet->disconnectWorksheets();

        return response()->json(['status' => 'ok', 'preview_token' => $token, 'previews' => $previews]);
    }

    // GET /tim-kerja/rab-generator/unduh/{token}
    public function unduh(string $token)
    {
        $this->authorize('generate', FileExcel::class);

        $entri = Cache::get("rab_report:{$token}");
        abort_if($entri === null, 404, 'Pratinjau sudah kedaluwarsa. Buat pratinjau ulang.');
        abort_unless($entri['user_id'] === Auth::id(), 403); // token terikat user pembuatnya
        abort_unless(Storage::disk('private')->exists($entri['path']), 404, 'Berkas hasil tidak ditemukan. Buat pratinjau ulang.');

        // Tanpa menghitung ulang: berkas yang sama dengan yang dipratinjau.
        return Storage::disk('private')->download($entri['path'], $entri['nama']);
    }

    /**
     * 03/06 S1: sheet harus milik file master aktif, kelompok harus milik sheet-nya.
     *
     * @return array<int, array{peta: array, header: bool, footer: bool, kelompok_baris: int[]}>
     *
     * @throws ValidationException
     */
    private function susunPilihan(FileExcel $file, array $sheets, RabReportService $service): array
    {
        $idSheet = array_map('intval', array_column($sheets, 'sheet_id'));

        $modelSheet = Sheet::where('file_excel_id', $file->id)
            ->whereIn('id', $idSheet)
            ->with([
                'header', 'footer',
                'kategori' => fn ($q) => $q->orderBy('urutan'),
                'kategori.kelompok' => fn ($q) => $q->orderBy('urutan'),
            ])
            ->get()
            ->keyBy('id');

        if ($modelSheet->count() !== count($idSheet)) {
            throw ValidationException::withMessages(['sheets' => 'Ada sheet yang bukan milik file master aktif.']);
        }

        $pilihan = [];
        foreach ($sheets as $item) {
            $sheet = $modelSheet[(int) $item['sheet_id']];
            $kelompok = $sheet->kategori->flatMap(fn ($k) => $k->kelompok)->keyBy('id');
            $idKelompok = array_map('intval', $item['kelompok_ids']);

            if (array_diff($idKelompok, $kelompok->keys()->all()) !== []) {
                throw ValidationException::withMessages(['sheets' => 'Ada kelompok yang bukan milik sheet terpilih.']);
            }

            $pilihan[] = [
                'peta' => $service->petaDariSheet($sheet),
                // Q1 (usulan bawaan): header/footer dapat dilepas, default ikut.
                'header' => (bool) ($item['header'] ?? true),
                'footer' => (bool) ($item['footer'] ?? true),
                'kelompok_baris' => $kelompok->only($idKelompok)->pluck('baris_awal')->map(fn ($v) => (int) $v)->values()->all(),
            ];
        }

        return $pilihan;
    }
}
