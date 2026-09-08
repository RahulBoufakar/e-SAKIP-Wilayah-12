<?php

namespace App\Http\Controllers\TimKerja;

use App\Http\Controllers\Concerns\GatesUsulanProgramKerja;
use App\Http\Controllers\Concerns\ResolvesTimKerjaSession;
use App\Http\Controllers\Controller;
use App\Models\AnalisaKinerja;
use App\Models\CapaianKinerja;
use App\Models\DetailKegiatan;
use App\Models\DokumenLaporanKegiatan;
use App\Models\Iku;
use App\Models\Pts;
use App\Models\TahunAnggaran;
use App\Models\TriwulanStatus;
use App\Models\UsulanProgramKerja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

class DashboardController extends Controller
{
    use ResolvesTimKerjaSession;
    use GatesUsulanProgramKerja;

    private const CACHE_TTL = 300;

    private const STATUS_LIST = ['draft', 'menunggu_validasi', 'approved', 'rejected'];

    private const MODUL = [
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

        // Cache dikunci ke kombinasi tahun anggaran + tim kerja (bukan per-user),
        // supaya sesama anggota satu Tim Kerja berbagi cache yang sama.
        $cacheKey = 'tim_kerja_dashboard_v3_'.$tahunAnggaranId.'_'.$timKerjaIds->sort()->implode('-');

        $data = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($tahunAnggaranId, $timKerjaIds) {
            return $this->buildDashboardData($tahunAnggaranId, $timKerjaIds);
        });

        return view('tim-kerja.dashboard.index', $data);
    }

    private function buildDashboardData(int $tahunAnggaranId, $timKerjaIds): array
    {
        $ikuIds = Iku::whereHas('timKerja', fn ($q) => $q->whereIn('tim_kerja.id', $timKerjaIds))
            ->whereHas('sasaranKegiatan', fn ($q) => $q->where('tahun_anggaran_id', $tahunAnggaranId))
            ->pluck('iku.id');

        $activeTahun = (int) TahunAnggaran::find($tahunAnggaranId)->tahun;
        $nextYear = $activeTahun + 1;
        $nextYearAvailable = $this->nextTahunAnggaranExists($tahunAnggaranId);

        // (1) Usulan Program Kerja
        $statusCounts = UsulanProgramKerja::whereIn('iku_id', $ikuIds)
            ->where('tahun', $activeTahun)
            ->selectRaw('status_validasi, count(*) as total')
            ->groupBy('status_validasi')
            ->pluck('total', 'status_validasi');

        $usulanStatusBreakdown = collect(self::STATUS_LIST)
            ->mapWithKeys(fn ($status) => [$status => (int) ($statusCounts[$status] ?? 0)]);

        $usulanPerIku = UsulanProgramKerja::whereIn('iku_id', $ikuIds)
            ->where('tahun', $activeTahun)
            ->with('iku:id,kode,deskripsi')
            ->select('iku_id')
            ->selectRaw('count(*) as total')
            ->groupBy('iku_id')
            ->get();

        // (2) Data Proker
        $dataProker = $this->dataProkerData($ikuIds, $activeTahun);

        // (3) Kalender Proker
        $kalenderBerjalan = $this->kalenderProkerData($ikuIds, $activeTahun);
        $kalenderHPlus1 = $this->kalenderProkerData($ikuIds, $nextYearAvailable ? $nextYear : null);

        // (4) Pelaporan Kegiatan
        $pelaporan = $this->pelaporanKegiatanData($ikuIds, $activeTahun);

        // (5) Tagging PTS
        $ptsTagging = $this->taggingPtsData($ikuIds, $activeTahun);

        // (6) Capaian & Analisis Kinerja — di-scope ke Triwulan Aktif saja,
        // konsisten dengan bagian lain dashboard ini yang tidak memakai tab TW1-4.
        $triwulanAktif = TriwulanStatus::with('triwulan')
            ->where('tahun_anggaran_id', $tahunAnggaranId)
            ->where('status', 'aktif')
            ->first();

        $rataCapaian = null;
        $kelengkapanRealisasi = null;
        $ikuCapaianChart = collect();
        $analisaBreakdown = null;

        if ($triwulanAktif) {
            // iku:target_pk wajib di-eager-load — getCapaianAttribute() sekarang
            // membagi realisasi terhadap Target PK (bukan target per-triwulan).
            $realisasiList = CapaianKinerja::whereIn('iku_id', $ikuIds)
                ->where('tahun_anggaran_id', $tahunAnggaranId)
                ->where('triwulan_id', $triwulanAktif->triwulan_id)
                ->with('iku:id,kode,target_pk')
                ->get();

            $capaianValues = $realisasiList->map(fn ($r) => $r->capaian)->filter(fn ($c) => $c !== null);
            $rataCapaian = $capaianValues->isNotEmpty() ? round($capaianValues->avg(), 2) : null;

            $terisi = $realisasiList->filter(fn ($r) => $r->realisasi !== null)->count();
            $kelengkapanRealisasi = [
                'total' => $ikuIds->count(),
                'terisi' => $terisi,
                'persen' => $ikuIds->count() > 0 ? round($terisi / $ikuIds->count() * 100) : 0,
            ];

            $ikuCapaianChart = $realisasiList->map(function ($r) {
                $targetPk = (float) ($r->iku->target_pk ?? 0);
                $target = $r->target !== null ? (float) $r->target : null;

                return [
                    'kode' => $r->iku->kode ?? '-',
                    'target' => (float) ($r->target ?? 0),
                    'realisasi' => (float) ($r->realisasi ?? 0),
                    // Nilai Realisasi Triwulan Ini = (Realisasi ÷ Target PK) x 100%
                    'nilai_realisasi' => $r->capaian ?? 0,
                    // Target Triwulan (%) = (Target Triwulan ÷ Target PK) x 100%
                    'target_persen' => ($target !== null && $targetPk > 0) ? round(($target / $targetPk) * 100, 2) : 0,
                ];
            });

            $totalIkuTw = $ikuIds->count();
            $analisaList = AnalisaKinerja::whereIn('iku_id', $ikuIds)
                ->where('tahun_anggaran_id', $tahunAnggaranId)
                ->where('triwulan_id', $triwulanAktif->triwulan_id)
                ->get();

            $analisaBreakdown = [
                'belum_diisi' => max($totalIkuTw - $analisaList->count(), 0),
                'menunggu_validasi' => $analisaList->where('status', 'menunggu_validasi')->count(),
                'disetujui' => $analisaList->where('status', 'disetujui')->count(),
                'ditolak' => $analisaList->where('status', 'ditolak')->count(),
            ];
        }

        // Daftar item ditolak lintas modul
        $itemDitolak = collect();

        $usulanDitolak = UsulanProgramKerja::whereIn('iku_id', $ikuIds)
            ->where('tahun', $activeTahun)
            ->where('status_validasi', 'rejected')
            ->with('iku:id,kode,deskripsi')
            ->get();

        foreach ($usulanDitolak as $row) {
            $itemDitolak->push([
                'modul' => 'Usulan Program Kerja',
                'iku_kode' => $row->iku->kode ?? '-',
                'iku_deskripsi' => $row->iku->deskripsi ?? '-',
                'triwulan' => 'Tahun '.$row->tahun,
                'catatan_revisi' => $row->catatan_revisi,
                'updated_at' => $row->updated_at,
                'url' => route('tim-kerja.usulan-program-kerja.show', $row->id),
            ]);
        }

        foreach (self::MODUL as $modelClass => $meta) {
            $rows = $modelClass::whereIn('iku_id', $ikuIds)
                ->where('tahun_anggaran_id', $tahunAnggaranId)
                ->where('status', 'ditolak')
                ->with(['iku:id,kode,deskripsi', 'triwulan:id,kode'])
                ->get();

            foreach ($rows as $row) {
                $itemDitolak->push([
                    'modul' => $meta['label'],
                    'iku_kode' => $row->iku->kode ?? '-',
                    'iku_deskripsi' => $row->iku->deskripsi ?? '-',
                    'triwulan' => $row->triwulan->kode ?? '-',
                    'catatan_revisi' => $row->catatan_revisi,
                    'updated_at' => $row->updated_at,
                    'url' => Route::has($meta['route']) ? route($meta['route'], ['iku' => $row->iku_id]) : null,
                ]);
            }
        }
        $itemDitolak = $itemDitolak->sortByDesc('updated_at')->take(10)->values();

        return compact(
            'usulanStatusBreakdown', 'usulanPerIku',
            'activeTahun', 'nextYear', 'nextYearAvailable',
            'dataProker', 'kalenderBerjalan', 'kalenderHPlus1', 'pelaporan', 'ptsTagging',
            'triwulanAktif', 'rataCapaian', 'kelengkapanRealisasi', 'ikuCapaianChart', 'analisaBreakdown',
            'itemDitolak'
        );
    }

    private function dataProkerData($ikuIds, ?int $tahun): array
    {
        $approved = UsulanProgramKerja::where('status_validasi', 'approved')
            ->where('tahun', $tahun)
            ->whereIn('iku_id', $ikuIds)
            ->with('detailKegiatan')
            ->get();

        $kunjunganLapangan = $approved->filter(fn ($u) => $u->detailKegiatan?->jenis_kegiatan === 'kunjungan_lapangan')->count();
        $lainnya = $approved->filter(fn ($u) => $u->detailKegiatan?->jenis_kegiatan === 'lainnya')->count();
        $draft = $approved->filter(fn ($u) => blank($u->detailKegiatan?->jenis_kegiatan))->count();

        return [
            'total' => $approved->count(),
            'kunjungan_lapangan' => $kunjunganLapangan,
            'lainnya' => $lainnya,
            'draft' => $draft,
        ];
    }

    private function kalenderProkerData($ikuIds, ?int $tahun): array
    {
        if (! $tahun) {
            return ['total' => 0, 'per_bulan' => array_fill(1, 12, 0)];
        }

        $detailList = DetailKegiatan::whereHas(
            'usulanProgramKerja',
            fn ($q) => $q->where('status_validasi', 'approved')->where('tahun', $tahun)->whereIn('iku_id', $ikuIds)
        )->get(['bulan_kegiatan']);

        $perBulan = array_fill(1, 12, 0);
        foreach ($detailList as $detail) {
            foreach (($detail->bulan_kegiatan ?? []) as $bulan) {
                if (isset($perBulan[$bulan])) {
                    $perBulan[$bulan]++;
                }
            }
        }

        return ['total' => $detailList->count(), 'per_bulan' => $perBulan];
    }

    private function pelaporanKegiatanData($ikuIds, ?int $tahun): array
    {
        $counts = DokumenLaporanKegiatan::whereHas(
            'laporan.proker.usulanProgramKerja',
            fn ($q) => $q->where('tahun', $tahun)->whereIn('iku_id', $ikuIds)
        )->whereIn('status_validasi', ['menunggu_validasi', 'disetujui', 'ditolak'])
            ->selectRaw('status_validasi, count(*) as total')
            ->groupBy('status_validasi')
            ->pluck('total', 'status_validasi');

        $belumDiunggah = DokumenLaporanKegiatan::whereHas(
            'laporan.proker.usulanProgramKerja',
            fn ($q) => $q->where('tahun', $tahun)->whereIn('iku_id', $ikuIds)
        )->where('status_validasi', 'belum_diunggah')->count();

        return [
            'belum_diunggah' => $belumDiunggah,
            'menunggu_validasi' => (int) ($counts['menunggu_validasi'] ?? 0),
            'disetujui' => (int) ($counts['disetujui'] ?? 0),
            'ditolak' => (int) ($counts['ditolak'] ?? 0),
        ];
    }

    // Tagging PTS per IKU (bukan per PTS seperti di Validator) — supaya Tim
    // Kerja bisa membandingkan progres tagging antar IKU miliknya sendiri.
    private function taggingPtsData($ikuIds, ?int $tahun): array
    {
        $totalPts = Pts::count();

        $perIku = Iku::whereIn('id', $ikuIds)
            ->orderBy('kode')
            ->get(['id', 'kode'])
            ->map(function ($iku) use ($totalPts, $tahun) {
                $ditagging = Pts::whereHas('usulanProgramKerja', function ($q) use ($iku, $tahun) {
                    $q->where('iku_id', $iku->id)->where('tahun', $tahun);
                })->count();

                return [
                    'kode' => $iku->kode,
                    'ditagging' => $ditagging,
                    'belum' => max($totalPts - $ditagging, 0),
                ];
            });

        return [
            'total_pts' => $totalPts,
            'per_iku' => $perIku,
        ];
    }
}