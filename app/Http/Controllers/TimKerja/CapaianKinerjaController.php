<?php

namespace App\Http\Controllers\TimKerja;

use App\Http\Controllers\Concerns\ResolvesTimKerjaSession;
use App\Http\Controllers\Controller;
use App\Models\Iku;
use App\Models\Triwulan;
use App\Models\TriwulanStatus;
use Illuminate\Http\Request;

class CapaianKinerjaController extends Controller
{
    use ResolvesTimKerjaSession;

    // GET /tim-kerja/capaian-kinerja?triwulan=TW1..TW4
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

        $triwulanAktifStatus = TriwulanStatus::where('tahun_anggaran_id', $tahunAnggaranId)
            ->where('status', 'aktif')
            ->first();

        $triwulanDipilih = $triwulanList->first(fn ($tw) => $tw->kode === strtoupper((string) $request->get('triwulan')))
            ?? $triwulanList->first(fn ($tw) => $triwulanAktifStatus && $tw->id === $triwulanAktifStatus->triwulan_id)
            ?? $triwulanList->first();

        $isTriwulanAktif = $triwulanDipilih && $triwulanAktifStatus && $triwulanDipilih->id === $triwulanAktifStatus->triwulan_id;

        $ikuList = collect();

        if ($triwulanDipilih) {
            $ikuList = Iku::with(['capaianKinerja' => fn ($q) => $q->where('triwulan_id', $triwulanDipilih->id)
                    ->where('tahun_anggaran_id', $tahunAnggaranId)])
                ->whereIn('tim_kerja_id', $timKerjaIds)
                ->whereHas('sasaranKegiatan', fn ($q) => $q->where('tahun_anggaran_id', $tahunAnggaranId))
                ->orderBy('kode')
                ->get();
        }

        return view('tim-kerja.capaian-kinerja.index', compact('ikuList', 'triwulanList', 'triwulanDipilih', 'isTriwulanAktif'));
    }
}