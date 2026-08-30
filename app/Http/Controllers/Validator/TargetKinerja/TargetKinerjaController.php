<?php

namespace App\Http\Controllers\Validator\TargetKinerja;

use App\Http\Controllers\Concerns\ResolvesActiveTahunAnggaran;
use App\Http\Controllers\Controller;
use App\Models\SasaranKegiatan;
use Illuminate\Http\Request;

class TargetKinerjaController extends Controller
{
    use ResolvesActiveTahunAnggaran;

    // GET /validator/target-kinerja — baca-saja, seluruh IKU & Tim Kerja penanggung jawab
    public function index(Request $request)
    {
        $tahunAnggaranId = $this->activeTahunAnggaranId($request);
        if (! $tahunAnggaranId) {
            return $this->missingTahunAnggaran('validator.layout.app', 'validator.dashboard');
        }

        $sasaranList = SasaranKegiatan::with(['iku.timKerja'])
            ->where('tahun_anggaran_id', $tahunAnggaranId)
            ->orderBy('kode')
            ->get();

        return view('validator.target-kinerja.index', compact('sasaranList'));
    }
}
