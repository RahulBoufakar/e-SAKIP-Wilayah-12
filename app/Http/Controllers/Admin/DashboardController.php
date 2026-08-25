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
            $capaianList = CapaianKinerja::whereIn('iku_id', $ikuIdsTahunIni)
                ->where('tahun_anggaran_id', $tahunAnggaranId)
                ->where('triwulan_id', $triwulanAktif->triwulan_id)
                ->get()
                ->map(fn ($r) => $r->capaian)
                ->filter(fn ($c) => $c !== null);
            $rataCapaian = $capaianList->isNotEmpty() ? round($capaianList->avg(), 2) : null;

            $realisasiTerisi = CapaianKinerja::whereIn('iku_id', $ikuIdsTahunIni)
                ->where('tahun_anggaran_id', $tahunAnggaranId)
                ->where('triwulan_id', $triwulanAktif->triwulan_id)
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

        // Target vs Realisasi per triwulan (TW1-TW4); asumsi id Triwulan 1-4
        // berurutan sesuai TriwulanSeeder (sama seperti asumsi lama).
        $targetRealisasiRaw = CapaianKinerja::whereIn('iku_id', $ikuIdsTahunIni)
            ->where('tahun_anggaran_id', $tahunAnggaranId)
            ->selectRaw('triwulan_id, SUM(target) as total_target, SUM(realisasi) as total_realisasi')
            ->groupBy('triwulan_id')
            ->get()
            ->keyBy('triwulan_id');

        $triwulanChartLabels = ['TW1', 'TW2', 'TW3', 'TW4'];
        $targetChartData = [];
        $realisasiChartData = [];
        foreach ([1, 2, 3, 4] as $triwulanId) {
            $row = $targetRealisasiRaw->get($triwulanId);
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