<?php

namespace App\Http\Controllers\Validator\TargetKinerja;

use App\Http\Controllers\Concerns\ResolvesActiveTahunAnggaran;
use App\Http\Controllers\Controller;
use App\Models\Iku;
use App\Models\Triwulan;
use Illuminate\Http\Request;

class RencanaAksiController extends Controller
{
    use ResolvesActiveTahunAnggaran;

    // GET /validator/rencana-aksi — baca-saja, disajikan dalam bentuk tabel (bukan textarea)
    public function index(Request $request)
    {
        $tahunAnggaranId = $this->activeTahunAnggaranId($request);
        if (! $tahunAnggaranId) {
            return $this->missingTahunAnggaran('validator.layout.app', 'validator.dashboard');
        }

        $triwulanList = Triwulan::orderBy('urutan')->get();

        $ikuList = Iku::with('rencanaAksi')
            ->whereHas('sasaranKegiatan', fn ($q) => $q->where('tahun_anggaran_id', $tahunAnggaranId))
            ->when($request->filled('search'), fn ($q) => $q->where('deskripsi', 'like', '%'.$request->search.'%'))
            ->orderBy('kode')
            ->paginate(15)
            ->withQueryString();

        return view('validator.target-kinerja.rencana-aksi.index', compact('ikuList', 'triwulanList'));
    }
}
