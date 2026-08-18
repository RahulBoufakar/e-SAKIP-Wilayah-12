<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ResolvesActiveTahunAnggaran;
use App\Http\Controllers\Controller;
use App\Models\Iku;
use App\Models\JumlahMahasiswa;
use App\Models\JumlahPts;
use App\Models\Realisasi;
use App\Models\RencanaAksi;
use App\Models\SasaranKegiatan;
use App\Models\TriwulanStatus;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ResolvesActiveTahunAnggaran;

    // GET /admin/dashboard (FR-D1/FR-D2, FR-D3: chart Chart.js)
    public function index(Request $request)
    {
        $tahunAnggaranId = $this->activeTahunAnggaranId($request);
        if (! $tahunAnggaranId) {
            return $this->missingTahunAnggaran();
        }

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

        // Rata-rata capaian & kelengkapan Realisasi/Rencana Aksi triwulan aktif
        $rataCapaian = null;
        $kelengkapanRealisasi = null;
        $kelengkapanRencanaAksi = null;

        if ($triwulanAktif) {
            $triwulanKode = strtolower($triwulanAktif->triwulan->kode);

            $capaianList = Realisasi::whereIn('iku_id', $ikuIdsTahunIni)
                ->where('triwulan', $triwulanKode)
                ->get()
                ->map(fn ($r) => $r->capaian)
                ->filter(fn ($c) => $c !== null);
            $rataCapaian = $capaianList->isNotEmpty() ? round($capaianList->avg(), 2) : null;

            $realisasiTerisi = Realisasi::whereIn('iku_id', $ikuIdsTahunIni)
                ->where('triwulan', $triwulanKode)
                ->whereNotNull('realisasi')
                ->count();
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
        }

        // Target vs Realisasi per triwulan (TW1-TW4)
        $targetRealisasiRaw = Realisasi::whereIn('iku_id', $ikuIdsTahunIni)
            ->selectRaw('triwulan, SUM(target) as total_target, SUM(realisasi) as total_realisasi')
            ->groupBy('triwulan')
            ->get()
            ->keyBy('triwulan');

        $triwulanChartLabels = ['TW1', 'TW2', 'TW3', 'TW4'];
        $targetChartData = [];
        $realisasiChartData = [];
        foreach (['tw1', 'tw2', 'tw3', 'tw4'] as $kode) {
            $row = $targetRealisasiRaw->get($kode);
            $targetChartData[] = (float) ($row->total_target ?? 0);
            $realisasiChartData[] = (float) ($row->total_realisasi ?? 0);
        }

        // Sebaran IKU per Tim Kerja
        $sebaranIkuPerTim = Iku::whereHas(
            'sasaranKegiatan',
            fn ($q) => $q->where('tahun_anggaran_id', $tahunAnggaranId)
        )
            ->with('timKerja')
            ->get()
            ->groupBy(fn ($iku) => $iku->timKerja->nama_tim ?? 'Tanpa Tim Kerja')
            ->map->count();

        // IKU tanpa Tim Kerja
        $ikuTanpaTim = Iku::whereHas(
            'sasaranKegiatan',
            fn ($q) => $q->where('tahun_anggaran_id', $tahunAnggaranId)
        )->whereNull('tim_kerja_id')->count();

        // Tren Jumlah Mahasiswa & PTS antar tahun
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

        $trenTahunLabels = $trenMahasiswa->keys()->merge($trenPts->keys())->unique()->sort()->values();

        return view('admin.dashboard.index', compact(
            'jumlahSasaran',
            'jumlahIku',
            'triwulanAktif',
            'rataCapaian',
            'kelengkapanRealisasi',
            'kelengkapanRencanaAksi',
            'triwulanChartLabels',
            'targetChartData',
            'realisasiChartData',
            'sebaranIkuPerTim',
            'ikuTanpaTim',
            'trenTahunLabels',
            'trenMahasiswa',
            'trenPts'
        ));
    }
}