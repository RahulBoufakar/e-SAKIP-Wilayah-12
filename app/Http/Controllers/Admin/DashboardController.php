<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ResolvesActiveTahunAnggaran;
use App\Http\Controllers\Controller;
use App\Models\Iku;
use App\Models\JumlahMahasiswa;
use App\Models\JumlahPts;
use App\Models\CapaianKinerja;
use App\Models\RencanaAksi;
use App\Models\SasaranKegiatan;
use App\Models\TriwulanStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    use ResolvesActiveTahunAnggaran;

    private const CACHE_TTL = 300;

    // GET /admin/dashboard
    public function index(Request $request)
    {
        $tahunAnggaranId = $this->activeTahunAnggaranId($request);
        if (! $tahunAnggaranId) {
            return $this->missingTahunAnggaran();
        }

        $data = Cache::remember("admin_dashboard_v5_{$tahunAnggaranId}", self::CACHE_TTL, function () use ($tahunAnggaranId) {
            return $this->buildDashboardData($tahunAnggaranId);
        });

        return view('admin.dashboard.index', $data);
    }

    private function buildDashboardData(int $tahunAnggaranId): array
    {
        $jumlahSasaran = SasaranKegiatan::where('tahun_anggaran_id', $tahunAnggaranId)->count();

        $ikuIdsTahunIni = Iku::whereHas(
            'sasaranKegiatan',
            fn ($q) => $q->where('tahun_anggaran_id', $tahunAnggaranId)
        )->pluck('id');

        $jumlahIku = $ikuIdsTahunIni->count();

        $triwulanAktif = TriwulanStatus::with('triwulan')
            ->where('tahun_anggaran_id', $tahunAnggaranId)
            ->where('status', 'aktif')
            ->first();

        // target_pk & kode wajib di-eager-load: kode untuk label chart per-IKU,
        // target_pk untuk penyebut rumus persentase.
        $capaianRows = CapaianKinerja::whereIn('iku_id', $ikuIdsTahunIni)
            ->where('tahun_anggaran_id', $tahunAnggaranId)
            ->with('iku:id,kode,target_pk')
            ->get();

       if ($triwulanAktif) {
            $capaianAktif = $capaianRows->where('triwulan_id', $triwulanAktif->triwulan_id);

            $capaianValues = $capaianAktif->map(fn ($r) => $r->capaian)->filter(fn ($c) => $c !== null);
            $rataCapaian = $capaianValues->isNotEmpty() ? round($capaianValues->avg(), 2) : null;

            $realisasiTerisi = $capaianAktif->filter(fn ($r) => $r->realisasi !== null)->count();
            $kelengkapanRealisasi = [
                'total' => $jumlahIku,
                'terisi' => $realisasiTerisi,
                'persen' => $jumlahIku > 0 ? round($realisasiTerisi / $jumlahIku * 100) : 0,
            ];

            $rencanaAksiTerisi = RencanaAksi::whereIn('iku_id', $ikuIdsTahunIni)
                ->where('triwulan_id', $triwulanAktif->triwulan_id)
                ->whereNotNull('uraian')
                ->count();
            $kelengkapanRencanaAksi = [
                'total' => $jumlahIku,
                'terisi' => $rencanaAksiTerisi,
                'persen' => $jumlahIku > 0 ? round($rencanaAksiTerisi / $jumlahIku * 100) : 0,
            ];

            // Realisasi & Target per IKU untuk Triwulan Aktif — pola perhitungan
            // sama seperti chart Sasaran Kegiatan di atas (dibagi Target PK,
            // bukan dijumlah/dirata-rata mentah), hanya di sini per-IKU (tidak
            // dirata-ratakan) supaya bisa dibandingkan antar-IKU satu per satu.
            $ikuCapaianTriwulanChart = $capaianAktif->map(function ($r) {
                $targetPk = $r->iku?->target_pk !== null ? (float) $r->iku->target_pk : null;
                $target = $r->target !== null ? (float) $r->target : null;

                return [
                    'kode' => $r->iku->kode ?? '-',
                    // Realisasi (%) = (Realisasi ÷ Target PK) x 100%
                    'realisasi_persen' => $r->capaian ?? 0,
                    // Target Triwulan (%) = (Target Triwulan ÷ Target PK) x 100%
                    'target_persen' => $this->persenTerhadapTargetPk($target, $targetPk) ?? 0,
                ];
            })->values();
        }

        // Realisasi Sasaran Kegiatan per Triwulan (%): dua garis pembanding,
        // masing-masing dirata-rata lintas seluruh IKU pada triwulan tsb —
        //   Rata-rata Realisasi        = (Realisasi ÷ Target PK) x 100%
        //   Rata-rata Target Triwulan  = (Target Triwulan ÷ Target PK) x 100%
        // Keduanya dibagi Target PK (bukan dijumlah mentah) supaya tetap dalam
        // skala 0-100% dan sebanding lintas-IKU meski satuan aslinya berbeda.
        $triwulanChartLabels = ['TW1', 'TW2', 'TW3', 'TW4'];
        $rataRealisasiChart = [];
        $rataTargetTriwulanChart = [];

        foreach ([1, 2, 3, 4] as $triwulanId) {
            $rowsTw = $capaianRows->where('triwulan_id', $triwulanId);

            $realisasiPersenValues = $rowsTw->map(fn ($r) => $r->capaian)->filter(fn ($v) => $v !== null);
            $targetPersenValues = $rowsTw
                ->map(fn ($r) => $this->persenTerhadapTargetPk(
                    $r->target !== null ? (float) $r->target : null,
                    $r->iku?->target_pk !== null ? (float) $r->iku->target_pk : null
                ))
                ->filter(fn ($v) => $v !== null);

            $rataRealisasiChart[] = $realisasiPersenValues->isNotEmpty() ? round($realisasiPersenValues->avg(), 2) : 0;
            $rataTargetTriwulanChart[] = $targetPersenValues->isNotEmpty() ? round($targetPersenValues->avg(), 2) : 0;
        }

        $sebaranIkuPerTim = Iku::whereHas(
            'sasaranKegiatan',
            fn ($q) => $q->where('tahun_anggaran_id', $tahunAnggaranId)
        )
            ->with('timKerja')
            ->get()
            ->flatMap(fn ($iku) => $iku->timKerja->isNotEmpty() ? $iku->timKerja->pluck('nama_tim') : collect(['Tanpa Tim Kerja']))
            ->countBy();

        $ikuTanpaTim = Iku::whereHas(
            'sasaranKegiatan',
            fn ($q) => $q->where('tahun_anggaran_id', $tahunAnggaranId)
        )->whereDoesntHave('timKerja')->count();

        // Tren dipisah per kategori (bukan digabung dalam satu chart) sesuai
        // permintaan, masing-masing dengan label tahun miliknya sendiri.
        $trenMahasiswa = JumlahMahasiswa::with('tahunAnggaran')
            ->get()
            ->groupBy(fn ($r) => $r->tahunAnggaran->tahun)
            ->map(fn ($rows) => $rows->sum('jumlah'))
            ->sortKeys();

        $trenPts = JumlahPts::with('tahunAnggaran')
            ->get()
            ->groupBy(fn ($r) => $r->tahunAnggaran->tahun)
            ->map(fn ($rows) => $rows->sum('jumlah'))
            ->sortKeys();

        return compact(
            'jumlahSasaran',
            'jumlahIku',
            'triwulanAktif',
            'rataCapaian',
            'kelengkapanRealisasi',
            'kelengkapanRencanaAksi',
            'triwulanChartLabels',
            'rataRealisasiChart',
            'rataTargetTriwulanChart',
            'ikuCapaianTriwulanChart',
            'sebaranIkuPerTim',
            'ikuTanpaTim',
            'trenMahasiswa',
            'trenPts'
        );
    }

    /** (nilai ÷ targetPk) x 100%, dibulatkan 2 desimal. Null jika salah satu operand tidak valid. */
    private function persenTerhadapTargetPk(?float $nilai, ?float $targetPk): ?float
    {
        if ($nilai === null || $targetPk === null || $targetPk <= 0) {
            return null;
        }

        return round(($nilai / $targetPk) * 100, 2);
    }
}