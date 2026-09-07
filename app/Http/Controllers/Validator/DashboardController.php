<?php

namespace App\Http\Controllers\Validator;

use App\Http\Controllers\Concerns\GatesUsulanProgramKerja;
use App\Http\Controllers\Concerns\ResolvesActiveTahunAnggaran;
use App\Http\Controllers\Controller;
use App\Models\AnalisaKinerja;
use App\Models\CapaianKinerja;
use App\Models\DetailKegiatan;
use App\Models\DokumenLaporanKegiatan;
use App\Models\Iku;
use App\Models\Pts;
use App\Models\TahunAnggaran;
use App\Models\Triwulan;
use App\Models\TriwulanStatus;
use App\Models\UsulanProgramKerja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    use ResolvesActiveTahunAnggaran;
    use GatesUsulanProgramKerja;

    private const CACHE_TTL = 300; // 5 menit
    private const BULAN_INDO = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    // GET /validator/dashboard
    public function index(Request $request)
    {
        $tahunAnggaranId = $this->activeTahunAnggaranId($request);
        $activeTahun = $tahunAnggaranId ? TahunAnggaran::find($tahunAnggaranId)?->tahun : null;
        $nextYear = $activeTahun ? $activeTahun + 1 : null;
        $nextYearAvailable = $tahunAnggaranId ? $this->nextTahunAnggaranExists($tahunAnggaranId) : false;

        $cacheKey = "validator_dashboard_v2_{$tahunAnggaranId}";

        $data = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($tahunAnggaranId, $activeTahun, $nextYear) {
            return [
                'usulan' => $this->usulanProkerData($activeTahun),
                'dataProker' => $this->dataProkerData($activeTahun),
                'kalenderBerjalan' => $this->kalenderProkerData($activeTahun),
                'kalenderHPlus1' => $this->kalenderProkerData($nextYear),
                'pelaporan' => $this->pelaporanKegiatanData($activeTahun),
                'ptsTagging' => $this->ptsTaggingData(),
                'triwulanList' => $this->triwulanKinerjaData($tahunAnggaranId),
            ];
        });

        $triwulanAktifKode = collect($data['triwulanList'])->firstWhere('is_aktif', true)['kode']
            ?? collect($data['triwulanList'])->first()['kode']
            ?? null;

        return view('validator.dashboard.index', array_merge($data, [
            'activeTahun' => $activeTahun,
            'nextYear' => $nextYear,
            'nextYearAvailable' => $nextYearAvailable,
            'bulanIndo' => self::BULAN_INDO,
            'triwulanAktifKode' => $triwulanAktifKode,
        ]));
    }

    private function usulanProkerData(?int $tahun): array
    {
        $counts = UsulanProgramKerja::where('tahun', $tahun)
            ->whereIn('status_validasi', ['menunggu_validasi', 'approved', 'rejected'])
            ->selectRaw('status_validasi, count(*) as total')
            ->groupBy('status_validasi')
            ->pluck('total', 'status_validasi');

        return [
            'menunggu_validasi' => (int) ($counts['menunggu_validasi'] ?? 0),
            'approved' => (int) ($counts['approved'] ?? 0),
            'rejected' => (int) ($counts['rejected'] ?? 0),
        ];
    }

    private function dataProkerData(?int $tahun): array
    {
        // Rule: hanya usulan yang sudah approved yang relevan buat Validator (bukan draft usulan).
        // Namun "jenis_kegiatan" pada detail masih bisa null (belum sempat divalidasi jenisnya) —
        // itu yang dilabeli "Draft" di sini, terpisah dari "Lainnya" yang sudah eksplisit dipilih.
        $approved = UsulanProgramKerja::where('status_validasi', 'approved')
            ->where('tahun', $tahun)
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

    private function kalenderProkerData(?int $tahun): array
    {
        if (! $tahun) {
            return ['total' => 0, 'per_bulan' => array_fill(1, 12, 0)];
        }

        $detailList = DetailKegiatan::whereHas(
            'usulanProgramKerja',
            fn ($q) => $q->where('status_validasi', 'approved')->where('tahun', $tahun)
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

    private function pelaporanKegiatanData(?int $tahun): array
    {
        $counts = DokumenLaporanKegiatan::whereHas(
            'laporan.proker.usulanProgramKerja',
            fn ($q) => $q->where('tahun', $tahun)
        )->whereIn('status_validasi', ['menunggu_validasi', 'disetujui', 'ditolak'])
            ->selectRaw('status_validasi, count(*) as total')
            ->groupBy('status_validasi')
            ->pluck('total', 'status_validasi');

        // "belum_diunggah" sengaja tetap ditampilkan (bukan status "draft" tim kerja,
        // melainkan indikator dokumen yang perlu ditindaklanjuti/ditagih oleh Validator).
        $belumDiunggah = DokumenLaporanKegiatan::whereHas(
            'laporan.proker.usulanProgramKerja',
            fn ($q) => $q->where('tahun', $tahun)
        )->where('status_validasi', 'belum_diunggah')->count();

        return [
            'belum_diunggah' => $belumDiunggah,
            'menunggu_validasi' => (int) ($counts['menunggu_validasi'] ?? 0),
            'disetujui' => (int) ($counts['disetujui'] ?? 0),
            'ditolak' => (int) ($counts['ditolak'] ?? 0),
        ];
    }

    private function ptsTaggingData(): array
    {
        $ptsList = Pts::withCount('usulanProgramKerja')->get(['id']);

        return [
            'total_pts_ditagging' => $ptsList->filter(fn ($p) => $p->usulan_program_kerja_count > 0)->count(),
            'total_pts_belum_ditagging' => $ptsList->filter(fn ($p) => $p->usulan_program_kerja_count === 0)->count(),
        ];
    }

    /**
     * Data Capaian & Analisis Kinerja untuk SEMUA triwulan sekaligus (dikirim ke
     * client, tab-switch dilakukan Alpine tanpa reload). Status 'draft' dikecualikan
     * karena dashboard ini khusus Validator (data baru relevan mulai menunggu_validasi).
     */
    private function triwulanKinerjaData(?int $tahunAnggaranId): array
    {
        $triwulanList = Triwulan::orderBy('urutan')->get();

        if (! $tahunAnggaranId) {
            $empty = ['menunggu_validasi' => 0, 'disetujui' => 0, 'ditolak' => 0];

            return $triwulanList->map(fn ($tw) => [
                'id' => $tw->id,
                'kode' => $tw->kode,
                'is_aktif' => false,
                'capaian' => $empty,
                'analisa' => $empty,
            ])->all();
        }

        $ikuIds = Iku::whereHas('sasaranKegiatan', fn ($q) => $q->where('tahun_anggaran_id', $tahunAnggaranId))->pluck('id');

        $triwulanAktifStatus = TriwulanStatus::where('tahun_anggaran_id', $tahunAnggaranId)
            ->where('status', 'aktif')
            ->first();

        $capaianCounts = CapaianKinerja::whereIn('iku_id', $ikuIds)
            ->where('tahun_anggaran_id', $tahunAnggaranId)
            ->whereIn('status', ['menunggu_validasi', 'disetujui', 'ditolak'])
            ->selectRaw('triwulan_id, status, count(*) as total')
            ->groupBy('triwulan_id', 'status')
            ->get()
            ->groupBy('triwulan_id');

        $analisaCounts = AnalisaKinerja::whereIn('iku_id', $ikuIds)
            ->where('tahun_anggaran_id', $tahunAnggaranId)
            ->whereIn('status', ['menunggu_validasi', 'disetujui', 'ditolak'])
            ->selectRaw('triwulan_id, status, count(*) as total')
            ->groupBy('triwulan_id', 'status')
            ->get()
            ->groupBy('triwulan_id');

        return $triwulanList->map(function ($tw) use ($capaianCounts, $analisaCounts, $triwulanAktifStatus) {
            return [
                'id' => $tw->id,
                'kode' => $tw->kode,
                'is_aktif' => $triwulanAktifStatus && $triwulanAktifStatus->triwulan_id === $tw->id,
                'capaian' => $this->mapKinerjaStatusCounts($capaianCounts->get($tw->id)),
                'analisa' => $this->mapKinerjaStatusCounts($analisaCounts->get($tw->id)),
            ];
        })->all();
    }

    private function mapKinerjaStatusCounts(?\Illuminate\Support\Collection $rows): array
    {
        return [
            'menunggu_validasi' => (int) ($rows?->firstWhere('status', 'menunggu_validasi')?->total ?? 0),
            'disetujui' => (int) ($rows?->firstWhere('status', 'disetujui')?->total ?? 0),
            'ditolak' => (int) ($rows?->firstWhere('status', 'ditolak')?->total ?? 0),
        ];
    }
}