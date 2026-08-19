<?php

namespace App\Http\Controllers\TimKerja;

use App\Http\Controllers\Concerns\ResolvesTimKerjaSession;
use App\Http\Controllers\Controller;
use App\Models\Iku;
use App\Models\Triwulan;
use Illuminate\Http\Request;

class RencanaAksiController extends Controller
{
    use ResolvesTimKerjaSession;

    // GET /tim-kerja/rencana-aksi — baca-saja, IKU difilter ke Tim Kerja user login
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

        $triwulanList = Triwulan::orderBy('urutan')->get();

        $ikuList = Iku::with('rencanaAksi')
            ->whereIn('tim_kerja_id', $timKerjaIds)
            ->whereHas('sasaranKegiatan', fn ($q) => $q->where('tahun_anggaran_id', $tahunAnggaranId))
            ->orderBy('kode')
            ->get();

        return view('tim-kerja.rencana-aksi.index', compact('ikuList', 'triwulanList'));
    }
}