<?php

namespace App\Http\Controllers\TimKerja;

use App\Http\Controllers\Concerns\ResolvesTimKerjaSession;
use App\Http\Controllers\Controller;
use App\Models\SasaranKegiatan;
use Illuminate\Http\Request;

class TargetKinerjaController extends Controller
{
    use ResolvesTimKerjaSession;

    // GET /tim-kerja/target-kinerja — baca-saja, IKU difilter ke Tim Kerja user login
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

        $sasaranList = SasaranKegiatan::with(['iku' => function ($q) use ($timKerjaIds) {
                $q->whereIn('tim_kerja_id', $timKerjaIds)->orderBy('kode');
            }])
            ->where('tahun_anggaran_id', $tahunAnggaranId)
            ->whereHas('iku', fn ($q) => $q->whereIn('tim_kerja_id', $timKerjaIds))
            ->orderBy('kode')
            ->get();

        return view('tim-kerja.target-kinerja.index', compact('sasaranList'));
    }
}