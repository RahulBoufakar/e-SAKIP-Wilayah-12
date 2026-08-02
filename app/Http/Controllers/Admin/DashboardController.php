<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ResolvesActiveTahunAnggaran;
use App\Http\Controllers\Controller;
use App\Models\Ikk;
use App\Models\Iku;
use App\Models\SasaranKegiatan;
use App\Models\TriwulanStatus;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ResolvesActiveTahunAnggaran;

    // GET /admin/dashboard (FR-D1/FR-D2). FR-D3 (chart Chart.js) sengaja belum
    // diimplementasikan di sini — eksplisit "opsional/nice-to-have" di PRD & Desain Sistem §6.
    public function index(Request $request)
    {
        $tahunAnggaranId = $this->activeTahunAnggaranId($request);
        if (! $tahunAnggaranId) {
            return $this->missingTahunAnggaran();
        }

        $jumlahSasaran = SasaranKegiatan::where('tahun_anggaran_id', $tahunAnggaranId)->count();

        $jumlahIku = Iku::whereHas(
            'sasaranKegiatan',
            fn ($q) => $q->where('tahun_anggaran_id', $tahunAnggaranId)
        )->count();

        // $jumlahIkk = Ikk::whereHas(
        //     'iku.sasaranKegiatan',
        //     fn ($q) => $q->where('tahun_anggaran_id', $tahunAnggaranId)
        // )->count();

        $triwulanAktif = TriwulanStatus::with('triwulan')
            ->where('tahun_anggaran_id', $tahunAnggaranId)
            ->where('status', 'aktif')
            ->first();

        return view('admin.dashboard.index', compact('jumlahSasaran', 'jumlahIku', 'triwulanAktif'));
    }
}
