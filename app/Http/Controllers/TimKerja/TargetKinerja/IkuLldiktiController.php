<?php

namespace App\Http\Controllers\TimKerja\TargetKinerja;

use App\Http\Controllers\Concerns\ResolvesTimKerjaSession;
use App\Http\Controllers\Controller;
use App\Models\Iku;
use Illuminate\Http\Request;

class IkuLldiktiController extends Controller
{
    use ResolvesTimKerjaSession;

    // GET /tim-kerja/iku-lldikti — seluruh IKU tahun anggaran aktif, TIDAK difilter tim;
    // baris milik Tim Kerja user login di-highlight di view.
    public function index(Request $request)
    {
        $tahunAnggaranId = $this->activeTahunAnggaranId($request);
        if (! $tahunAnggaranId) {
            return $this->missingTahunAnggaran('tim-kerja.layout.app', 'tim-kerja.dashboard');
        }

        $timKerjaIds = $this->activeTimKerjaIds();

        $ikuList = Iku::with(['sasaranKegiatan', 'timKerja'])
            ->whereHas('sasaranKegiatan', fn ($q) => $q->where('tahun_anggaran_id', $tahunAnggaranId))
            ->orderBy('kode')
            ->get();

        return view('tim-kerja.target-kinerja.iku-lldikti.index', compact('ikuList', 'timKerjaIds'));
    }
}