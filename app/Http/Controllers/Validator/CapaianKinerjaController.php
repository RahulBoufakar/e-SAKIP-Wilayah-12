<?php

namespace App\Http\Controllers\Validator;

use App\Http\Controllers\Concerns\ResolvesActiveTahunAnggaran;
use App\Http\Controllers\Controller;
use App\Models\Iku;
use App\Models\Triwulan;
use App\Models\TriwulanStatus;
use Illuminate\Http\Request;

class CapaianKinerjaController extends Controller
{
    use ResolvesActiveTahunAnggaran;

    // GET /validator/capaian-kinerja?triwulan=TW1..TW4 — seluruh Tim Kerja, tidak difilter
    public function index(Request $request)
    {
        $tahunAnggaranId = $this->activeTahunAnggaranId($request);
        if (! $tahunAnggaranId) {
            return $this->missingTahunAnggaran('validator.layout.app', 'validator.dashboard');
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
                ->whereHas('sasaranKegiatan', fn ($q) => $q->where('tahun_anggaran_id', $tahunAnggaranId))
                ->orderBy('kode')
                ->get();
        }

        return view('validator.capaian-kinerja.index', compact('ikuList', 'triwulanList', 'triwulanDipilih', 'isTriwulanAktif'));
    }
}