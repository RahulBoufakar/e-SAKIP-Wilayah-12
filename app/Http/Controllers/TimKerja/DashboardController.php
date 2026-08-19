<?php

namespace App\Http\Controllers\TimKerja;

use App\Http\Controllers\Concerns\ResolvesTimKerjaSession;
use App\Http\Controllers\Controller;
use App\Models\AnalisaKinerja;
use App\Models\CapaianKinerja;
use App\Models\Iku;
use App\Models\PelaporanKegiatan;
use App\Models\Realisasi;
use App\Models\TriwulanStatus;
use App\Models\UsulanProgramKerja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class DashboardController extends Controller
{
    use ResolvesTimKerjaSession;

    private const STATUS_LIST = ['draft', 'menunggu_validasi', 'disetujui', 'ditolak'];

    /**
     * Modul pengajuan yang dipantau di dashboard ini. 'route' MENGASUMSIKAN
     * konvensi penamaan route halaman modul yang akan dibangun kemudian
     * (tim-kerja.<modul>.index) — dibungkus Route::has() di bawah supaya
     * tidak error selama halaman sumbernya belum ada.
     */
    private const MODUL = [
        UsulanProgramKerja::class => ['label' => 'Usulan Program Kerja', 'route' => 'tim-kerja.usulan-program-kerja.index'],
        PelaporanKegiatan::class => ['label' => 'Pelaporan Kegiatan', 'route' => 'tim-kerja.pelaporan-kegiatan.index'],
        CapaianKinerja::class => ['label' => 'Capaian Kinerja', 'route' => 'tim-kerja.capaian-kinerja.index'],
        AnalisaKinerja::class => ['label' => 'Analisa Kinerja', 'route' => 'tim-kerja.analisa-kinerja.index'],
    ];

    // GET /tim-kerja/dashboard
    public function index(Request $request)
    {
        $tahunAnggaranId = $this->activeTahunAnggaranId($request);
        if (! $tahunAnggaranId) {
            return $this->missingTahunAnggaran('tim-kerja.layout.app', 'tim-kerja.dashboard');
        }

        $timKerjaIds = $this->activeTimKerjaIds();
        if ($timKerjaIds->isEmpty()) {
            return view('admin.layout.app-error-content', [
                'errorMessage' => 'Anda belum ditugaskan ke Tim Kerja manapun. Hubungi Administrator.',
                'layout' => 'tim-kerja.layout.app',
                'backRoute' => 'tim-kerja.dashboard',
            ]);
        }

        $ikuIds = Iku::whereIn('tim_kerja_id', $timKerjaIds)
            ->whereHas('sasaranKegiatan', fn ($q) => $q->where('tahun_anggaran_id', $tahunAnggaranId))
            ->pluck('id');

        // (1) Breakdown status Usulan Program Kerja tahun berjalan
        $statusCounts = UsulanProgramKerja::whereIn('iku_id', $ikuIds)
            ->where('tahun_anggaran_id', $tahunAnggaranId)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $usulanStatusBreakdown = collect(self::STATUS_LIST)
            ->mapWithKeys(fn ($status) => [$status => (int) ($statusCounts[$status] ?? 0)]);
        $jumlahUsulan = $usulanStatusBreakdown->sum();

        // (1) Jumlah usulan dikelompokkan per IKU
        $usulanPerIku = UsulanProgramKerja::whereIn('iku_id', $ikuIds)
            ->where('tahun_anggaran_id', $tahunAnggaranId)
            ->with('iku:id,kode,deskripsi')
            ->select('iku_id')
            ->selectRaw('count(*) as total')
            ->groupBy('iku_id')
            ->get();

        // Triwulan berjalan (aktif) untuk tahun anggaran ini
        $triwulanAktif = TriwulanStatus::with('triwulan')
            ->where('tahun_anggaran_id', $tahunAnggaranId)
            ->where('status', 'aktif')
            ->first();

        // (1) Ringkasan realisasi/capaian triwulan berjalan + (2) data chart
        $rataCapaian = null;
        $kelengkapanRealisasi = null;
        $ikuCapaianChart = collect();

        if ($triwulanAktif) {
            $triwulanKode = strtolower($triwulanAktif->triwulan->kode);

            $realisasiList = Realisasi::whereIn('iku_id', $ikuIds)
                ->where('triwulan', $triwulanKode)
                ->with('iku:id,kode')
                ->get();

            $capaianValues = $realisasiList->map(fn ($r) => $r->capaian)->filter(fn ($c) => $c !== null);
            $rataCapaian = $capaianValues->isNotEmpty() ? round($capaianValues->avg(), 2) : null;

            $terisi = $realisasiList->whereNotNull('realisasi')->count();
            $kelengkapanRealisasi = [
                'total' => $ikuIds->count(),
                'terisi' => $terisi,
                'persen' => $ikuIds->count() > 0 ? round($terisi / $ikuIds->count() * 100) : 0,
            ];

            $ikuCapaianChart = $realisasiList->map(fn ($r) => [
                'kode' => $r->iku->kode ?? '-',
                'target' => (float) ($r->target ?? 0),
                'realisasi' => (float) ($r->realisasi ?? 0),
            ]);
        }

        // (3) Daftar item ditolak lintas 4 modul, terbaru lebih dulu
        $itemDitolak = collect();
        // SESUDAH
        foreach (self::MODUL as $modelClass => $meta) {
            $withRelations = $modelClass === UsulanProgramKerja::class
                ? ['iku:id,kode,deskripsi']
                : ['iku:id,kode,deskripsi', 'triwulan:id,kode'];

            $rows = $modelClass::whereIn('iku_id', $ikuIds)
                ->where('tahun_anggaran_id', $tahunAnggaranId)
                ->where('status', 'ditolak')
                ->with($withRelations)
                ->get();

            foreach ($rows as $row) {
                $itemDitolak->push([
                    'modul' => $meta['label'],
                    'iku_kode' => $row->iku->kode ?? '-',
                    'iku_deskripsi' => $row->iku->deskripsi ?? '-',
                    'triwulan' => $modelClass === UsulanProgramKerja::class
                        ? ($row->tahun === 'h_plus_1' ? 'TA+1' : 'TA Berjalan')
                        : ($row->triwulan->kode ?? '-'),
                    'catatan_revisi' => $row->catatan_revisi,
                    'updated_at' => $row->updated_at,
                    'url' => $modelClass === UsulanProgramKerja::class
                        ? route('tim-kerja.usulan-program-kerja.show', $row->id)
                        : (Route::has($meta['route']) ? route($meta['route'], ['iku' => $row->iku_id]) : null),
                ]);
            }
        }
        $itemDitolak = $itemDitolak->sortByDesc('updated_at')->take(10)->values();

        return view('tim-kerja.dashboard.index', compact(
            'jumlahUsulan',
            'usulanStatusBreakdown',
            'usulanPerIku',
            'triwulanAktif',
            'rataCapaian',
            'kelengkapanRealisasi',
            'ikuCapaianChart',
            'itemDitolak'
        ));
    }
}