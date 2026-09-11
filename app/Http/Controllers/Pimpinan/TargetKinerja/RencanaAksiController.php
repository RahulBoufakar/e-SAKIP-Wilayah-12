<?php

namespace App\Http\Controllers\Pimpinan\TargetKinerja;

use App\Http\Controllers\Concerns\ResolvesActiveTahunAnggaran;
use App\Http\Controllers\Controller;
use App\Models\Iku;
use App\Models\Triwulan;
use Illuminate\Http\Request;

class RencanaAksiController extends Controller
{
    use ResolvesActiveTahunAnggaran;

    // GET /pimpinan/rencana-aksi — baca-saja, disajikan dalam bentuk tabel, lintas semua Tim Kerja
    public function index(Request $request)
    {
        $tahunAnggaranId = $this->activeTahunAnggaranId($request);
        if (! $tahunAnggaranId) {
            return $this->missingTahunAnggaran('pimpinan.layout.app', 'pimpinan.dashboard');
        }

        $triwulanList = Triwulan::orderBy('urutan')->get();

        $ikuList = Iku::with('rencanaAksi')
            ->whereHas('sasaranKegiatan', fn ($q) => $q->where('tahun_anggaran_id', $tahunAnggaranId))
            ->when($request->filled('search'), fn ($q) => $q->where('deskripsi', 'like', '%'.$request->search.'%'))
            ->orderBy('kode')
            ->paginate(15)
            ->withQueryString();

        return view('pimpinan.target-kinerja.rencana-aksi.index', compact('ikuList', 'triwulanList'));
    }
}
