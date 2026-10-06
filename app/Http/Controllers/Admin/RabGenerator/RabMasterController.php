<?php

namespace App\Http\Controllers\Admin\RabGenerator;

use App\Events\ActivityOccurred;
use App\Http\Controllers\Controller;
use App\Models\FileExcel;
use App\Models\Sheet;
use App\Services\RabMasterAnalyzer;
use App\Services\RabMasterInspector;
use App\Services\RabMasterStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Exception as PhpSpreadsheetException;
use RuntimeException;

/**
 * RAB Generator 04-alur-pengguna.md §A: admin mengunggah master, meninjau hasil deteksi,
 * mengoreksi, lalu mengaktifkan. Seluruh fitur berada di belakang
 * config('rab_generator.enabled') (08: bangun berdampingan dengan alur lama).
 */
class RabMasterController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless(config('rab_generator.enabled'), 404);

            return $next($request);
        });
    }

    // GET /admin/rab-generator
    public function index()
    {
        $this->authorize('viewAny', FileExcel::class);

        $daftar = FileExcel::with('pengunggah')->withCount('sheets')->orderByDesc('id')->paginate(15);

        return view('admin.rab-generator.index', compact('daftar'));
    }

    // POST /admin/rab-generator
    public function store(Request $request, RabMasterAnalyzer $analyzer, RabMasterStore $store)
    {
        $this->authorize('create', FileExcel::class);

        $request->validate([
            'file' => 'required|file|mimes:xlsx|max:20480',
        ], [
            'file.required' => 'File master wajib diunggah.',
            'file.mimes' => 'File harus berformat Excel (.xlsx).',
            'file.max' => 'Ukuran file maksimal 20 MB.',
        ]);

        $upload = $request->file('file');
        $path = $upload->store('rab-master', 'private'); // disk private: tidak pernah bisa diakses lewat URL publik

        try {
            $hasil = $analyzer->analyze(Storage::disk('private')->path($path));

            $fileExcel = DB::transaction(function () use ($upload, $path, $store, $hasil) {
                $file = FileExcel::create([
                    'nama_file' => $upload->getClientOriginalName(),
                    'path' => $path,
                    'hash_sha256' => hash_file('sha256', $upload->getRealPath()),
                    'diunggah_oleh' => Auth::id(),
                ]);
                $store->simpan($file, $hasil);

                return $file;
            });
        } catch (PhpSpreadsheetException $e) {
            Storage::disk('private')->delete($path);

            return back()->with('feedback', ['type' => 'error', 'message' => 'File tidak dapat dibaca sebagai Excel (.xlsx) yang valid.']);
        } catch (RuntimeException $e) {
            Storage::disk('private')->delete($path);

            return back()->with('feedback', ['type' => 'error', 'message' => $e->getMessage()]);
        }

        event(new ActivityOccurred(
            subject: $fileExcel,
            description: "mengunggah file master RAB \"{$fileExcel->nama_file}\"",
            causer: Auth::user(),
        ));

        return redirect()->route('admin.rab-generator.show', $fileExcel)
            ->with('feedback', ['type' => 'success', 'message' => 'File diunggah. Tinjau hasil deteksi, koreksi bila perlu, lalu aktifkan.']);
    }

    // GET /admin/rab-generator/{fileExcel}
    public function show(FileExcel $fileExcel, RabMasterAnalyzer $analyzer)
    {
        $this->authorize('view', $fileExcel);

        $fileExcel->load([
            'pengunggah',
            'sheets' => fn ($q) => $q->orderBy('urutan'),
            'sheets.header',
            'sheets.footer',
            'sheets.kategori' => fn ($q) => $q->orderBy('urutan'),
            'sheets.kategori.kelompok' => fn ($q) => $q->orderBy('urutan'),
        ]);

        $tersimpan = $fileExcel->peringatan ?? [];
        $peringatanFile = $tersimpan['file'] ?? [];
        $peringatanSheet = [];

        foreach ($fileExcel->sheets as $sheet) {
            // Struktur (nama kosong, kode ganda) dihitung dari database => hilang setelah admin memperbaikinya.
            $struktur = $analyzer->peringatanStruktur($sheet->kategori->map(fn ($k) => [
                'kode_kategori' => $k->kode_kategori,
                'baris_awal' => $k->baris_awal,
                'kelompok' => $k->kelompok->map(fn ($g) => [
                    'kode_kelompok' => $g->kode_kelompok,
                    'nama_kelompok' => $g->nama_kelompok,
                    'baris_awal' => $g->baris_awal,
                ])->all(),
            ])->all());

            // Dicari lewat indeks array: kode_sheet memuat titik ("7735.951"), jadi bukan data_get().
            $peringatanSheet[$sheet->id] = array_merge($tersimpan['sheet'][$sheet->kode_sheet] ?? [], $struktur);
        }

        return view('admin.rab-generator.show', compact('fileExcel', 'peringatanFile', 'peringatanSheet'));
    }

    // PUT /admin/rab-generator/{fileExcel}/aktifkan
    public function aktifkan(FileExcel $fileExcel)
    {
        $this->authorize('activate', $fileExcel);

        $fileExcel->aktifkan();

        event(new ActivityOccurred(
            subject: $fileExcel,
            description: "mengaktifkan file master RAB \"{$fileExcel->nama_file}\"",
            causer: Auth::user(),
        ));

        return back()->with('feedback', ['type' => 'success', 'message' => 'File master diaktifkan.']);
    }

    // PUT /admin/rab-generator/sheet/{sheet} — koreksi header/footer dan nama kelompok
    public function updateSheet(Request $request, Sheet $sheet, RabMasterInspector $inspector)
    {
        $fileExcel = $sheet->fileExcel;
        $this->authorize('update', $fileExcel);

        $sheet->load(['header', 'footer', 'kategori.kelompok']);
        $bag = "sheet{$sheet->id}"; // kantong galat terpisah per sheet (satu halaman memuat banyak form)

        $rules = ['nama' => 'nullable|array', 'nama.*' => 'nullable|string|max:255'];
        foreach (['header', 'footer'] as $blok) {
            if ($sheet->{$blok}) {
                $rules["{$blok}_awal"] = 'required|integer|min:1';
                $rules["{$blok}_akhir"] = "required|integer|gte:{$blok}_awal";
            }
        }

        $data = $request->validateWithBag($bag, $rules, [
            'header_awal.required' => 'Baris awal header wajib diisi.',
            'header_akhir.required' => 'Baris akhir header wajib diisi.',
            'header_akhir.gte' => 'Baris akhir header tidak boleh lebih kecil dari baris awal.',
            'footer_awal.required' => 'Baris awal footer wajib diisi.',
            'footer_akhir.required' => 'Baris akhir footer wajib diisi.',
            'footer_akhir.gte' => 'Baris akhir footer tidak boleh lebih kecil dari baris awal.',
            '*.integer' => 'Nomor baris harus berupa bilangan bulat.',
            '*.min' => 'Nomor baris minimal 1.',
        ]);

        $nama = $data['nama'] ?? [];
        $idKelompok = $sheet->kategori->flatMap(fn ($k) => $k->kelompok)->pluck('id')->all();
        abort_if(array_diff(array_map('intval', array_keys($nama)), $idKelompok) !== [], 422, 'Ada kelompok yang bukan milik sheet ini.');

        $berubah = [];
        foreach (['header', 'footer'] as $blok) {
            if ($sheet->{$blok} && ((int) $sheet->{$blok}->baris_awal !== (int) $data["{$blok}_awal"] || (int) $sheet->{$blok}->baris_akhir !== (int) $data["{$blok}_akhir"])) {
                $berubah[] = $blok;
            }
        }

        if ($berubah !== []) {
            $this->validasiRentang($sheet, $data, $berubah, $fileExcel, $inspector, $bag);
        }

        $perubahan = [];
        DB::transaction(function () use ($sheet, $data, $berubah, $nama, &$perubahan) {
            foreach ($berubah as $blok) {
                $sheet->{$blok}->update([
                    'baris_awal' => $data["{$blok}_awal"],
                    'baris_akhir' => $data["{$blok}_akhir"],
                    'otomatis' => false, // ditimpa admin
                ]);
                $perubahan[] = $blok;
            }

            $namaBerubah = 0;
            foreach ($sheet->kategori->flatMap(fn ($k) => $k->kelompok) as $kelompok) {
                if (! array_key_exists($kelompok->id, $nama)) {
                    continue;
                }
                $baru = trim((string) $nama[$kelompok->id]);
                $baru = $baru === '' ? null : $baru;

                if ($baru !== $kelompok->nama_kelompok) {
                    $kelompok->update(['nama_kelompok' => $baru]);
                    $namaBerubah++;
                }
            }
            if ($namaBerubah > 0) {
                $perubahan[] = "{$namaBerubah} nama kelompok";
            }
        });

        if ($perubahan === []) {
            return back()->with('feedback', ['type' => 'success', 'message' => 'Tidak ada perubahan.']);
        }

        event(new ActivityOccurred(
            subject: $sheet,
            description: "mengoreksi struktur master RAB sheet {$sheet->kode_sheet} (".implode(', ', $perubahan).')',
            causer: Auth::user(),
        ));

        return back()->with('feedback', ['type' => 'success', 'message' => 'Koreksi disimpan.']);
    }

    /**
     * 03, aturan validasi: urutan header < RO < kategori/kelompok < footer, tidak melebihi jumlah
     * baris sheet, tidak memotong merge cell. Hanya blok yang berubah yang diperiksa (membuka file
     * master hanya bila perlu).
     *
     * @throws ValidationException
     */
    private function validasiRentang(Sheet $sheet, array $data, array $berubah, FileExcel $fileExcel, RabMasterInspector $inspector, string $bag): void
    {
        $galat = [];

        // Baris terakhir isi RAB: akhir RO, komponen, dan kelompok (array, bukan argumen terpisah: max() butuh >= 2 nilai).
        $akhirBadan = (int) max(array_merge(
            [$sheet->ro_baris_akhir],
            $sheet->kategori->pluck('baris_akhir')->all(),
            $sheet->kategori->flatMap(fn ($k) => $k->kelompok)->pluck('baris_akhir')->all()
        ));

        if (in_array('header', $berubah, true) && (int) $data['header_akhir'] >= (int) $sheet->ro_baris_awal) {
            $galat['header_akhir'] = "Baris akhir header harus sebelum baris RO (baris {$sheet->ro_baris_awal}).";
        }
        if (in_array('footer', $berubah, true) && (int) $data['footer_awal'] <= $akhirBadan) {
            $galat['footer_awal'] = "Baris awal footer harus setelah isi RAB (baris terakhir {$akhirBadan}).";
        }

        $path = Storage::disk('private')->path($fileExcel->path);
        if (! is_file($path)) {
            $galat['file'] = 'File master tidak ditemukan di penyimpanan.';
        } else {
            $info = $inspector->infoSheet($path, $sheet->kode_sheet);

            foreach ($berubah as $blok) {
                $awal = (int) $data["{$blok}_awal"];
                $akhir = (int) $data["{$blok}_akhir"];

                if ($akhir > $info['total_baris']) {
                    $galat["{$blok}_akhir"] ??= "Baris akhir {$blok} melebihi jumlah baris sheet ({$info['total_baris']}).";
                }
                if ($merge = RabMasterInspector::mergeTerpotong($info['merges'], $awal, $akhir)) {
                    $galat["{$blok}_akhir"] ??= "Rentang {$blok} memotong merge cell {$merge}.";
                }
            }
        }

        if ($galat !== []) {
            throw ValidationException::withMessages($galat)->errorBag($bag);
        }
    }
}
