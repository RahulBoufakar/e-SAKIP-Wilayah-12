<?php

namespace App\Http\Controllers\Pimpinan;

use App\Events\ActivityOccurred;
use App\Http\Controllers\Concerns\ResolvesActiveTahunAnggaran;
use App\Http\Controllers\Controller;
use App\Models\LaporanKinerja;
use App\Models\TahunAnggaran;
use App\Models\Triwulan;
use App\Services\LaporanKinerjaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class LaporanKinerjaController extends Controller
{
    use ResolvesActiveTahunAnggaran;

    private const JENIS_VALID = ['bulanan', 'triwulanan', 'tahunan'];

    private const BULAN_INDO = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    // GET /pimpinan/laporan?jenis=&tahun_anggaran_id= — riwayat + arsip tahun sebelumnya (§3.1)
    public function index(Request $request)
    {
        $this->authorize('viewAny', LaporanKinerja::class);

        $tahunAnggaranId = $request->filled('tahun_anggaran_id')
            ? (int) $request->tahun_anggaran_id
            : $this->activeTahunAnggaranId($request);

        if (! $tahunAnggaranId) {
            return $this->missingTahunAnggaran('pimpinan.layout.app', 'pimpinan.dashboard');
        }

        $jenis = in_array($request->get('jenis'), self::JENIS_VALID, true) ? $request->get('jenis') : null;

        $laporanList = LaporanKinerja::with(['triwulan', 'generatedBy', 'tahunAnggaran'])
            ->where('tahun_anggaran_id', $tahunAnggaranId)
            ->when($jenis, fn ($q) => $q->where('jenis', $jenis))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('pimpinan.laporan.index', [
            'laporanList' => $laporanList,
            'jenis' => $jenis,
            'tahunAnggaranId' => $tahunAnggaranId,
            'tahunOptions' => TahunAnggaran::orderByDesc('tahun')->get(['id', 'tahun']),
            'triwulanList' => Triwulan::orderBy('urutan')->get(),
            'bulanIndo' => self::BULAN_INDO,
            'routePrefix' => 'pimpinan.laporan',
        ]);
    }

    // POST /pimpinan/laporan/generate
    public function generate(Request $request, LaporanKinerjaService $service)
    {
        $this->authorize('generate', LaporanKinerja::class);

        $data = $request->validate([
            'tahun_anggaran_id' => 'required|exists:tahun_anggaran,id',
            'jenis' => ['required', Rule::in(self::JENIS_VALID)],
            'bulan' => 'required_if:jenis,bulanan|nullable|integer|min:1|max:12',
            'triwulan_id' => 'required_if:jenis,triwulanan|nullable|exists:triwulan,id',
        ], [
            'jenis.required' => 'Jenis laporan wajib dipilih.',
            'bulan.required_if' => 'Bulan wajib dipilih untuk laporan bulanan.',
            'triwulan_id.required_if' => 'Triwulan wajib dipilih untuk laporan triwulanan.',
        ]);

        $laporan = match ($data['jenis']) {
            'bulanan' => $service->generateBulanan((int) $data['tahun_anggaran_id'], (int) $data['bulan'], Auth::id()),
            'triwulanan' => $service->generateTriwulanan((int) $data['tahun_anggaran_id'], (int) $data['triwulan_id'], Auth::id()),
            'tahunan' => $service->generateTahunan((int) $data['tahun_anggaran_id'], Auth::id()),
        };

        // §7: generate manual -> tercatat di Audit Log saja (recipients kosong = tanpa notifikasi bell)
        event(new ActivityOccurred(
            subject: $laporan,
            description: "meminta generate manual Laporan Kinerja \"{$laporan->label}\"",
            causer: Auth::user(),
        ));

        return back()->with('feedback', ['type' => 'success', 'message' => 'Laporan sedang diproses. Silakan tunggu beberapa saat.']);
    }

    // GET /pimpinan/laporan/{laporanKinerja}/unduh
    public function unduh(LaporanKinerja $laporanKinerja)
    {
        $this->authorize('download', $laporanKinerja);

        abort_unless($laporanKinerja->status === 'berhasil' && $laporanKinerja->file_path, 404);
        abort_unless(Storage::disk('laporan')->exists($laporanKinerja->file_path), 404);

        return Storage::disk('laporan')->download($laporanKinerja->file_path, $laporanKinerja->label.'.pdf');
    }

    // GET /pimpinan/laporan/status?ids[]=1&ids[]=2 — polling status "diproses" (§7.1)
    public function status(Request $request)
    {
        $this->authorize('viewAny', LaporanKinerja::class);

        $ids = array_map('intval', (array) $request->query('ids', []));

        return response()->json(
            LaporanKinerja::whereIn('id', $ids)->pluck('status', 'id')
        );
    }
}
