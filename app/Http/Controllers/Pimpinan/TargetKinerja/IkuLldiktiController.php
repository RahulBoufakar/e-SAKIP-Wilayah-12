<?php

namespace App\Http\Controllers\Pimpinan\TargetKinerja;

use App\Http\Controllers\Concerns\ResolvesActiveTahunAnggaran;
use App\Http\Controllers\Controller;
use App\Models\SasaranKegiatan;
use App\Models\Triwulan;
use App\Models\TriwulanStatus;
use Illuminate\Http\Request;

class IkuLldiktiController extends Controller
{
    use ResolvesActiveTahunAnggaran;

    // GET /pimpinan/iku-lldikti?triwulan=TW1..TW4 — baca-saja, lintas semua Tim Kerja
    public function index(Request $request)
    {
        $tahunAnggaranId = $this->activeTahunAnggaranId($request);
        if (! $tahunAnggaranId) {
            return $this->missingTahunAnggaran('pimpinan.layout.app', 'pimpinan.dashboard');
        }

        $triwulanList = Triwulan::orderBy('urutan')->get();

        $triwulanAktifStatus = TriwulanStatus::where('tahun_anggaran_id', $tahunAnggaranId)
            ->where('status', 'aktif')
            ->first();

        $triwulanDipilih = $triwulanList->first(fn ($tw) => $tw->kode === strtoupper((string) $request->get('triwulan')))
            ?? $triwulanList->first(fn ($tw) => $triwulanAktifStatus && $tw->id === $triwulanAktifStatus->triwulan_id)
            ?? $triwulanList->first();

        $sasaranList = collect();

        if ($triwulanDipilih) {
            $sasaranList = SasaranKegiatan::with(['iku' => function ($q) use ($triwulanDipilih, $tahunAnggaranId) {
                    $q->with(['timKerja', 'capaianKinerja' => fn ($c) => $c->where('triwulan_id', $triwulanDipilih->id)
                        ->where('tahun_anggaran_id', $tahunAnggaranId)])
                    ->orderBy('kode');
                }])
                ->where('tahun_anggaran_id', $tahunAnggaranId)
                ->orderBy('kode')
                ->get();
        }

        return view('pimpinan.target-kinerja.iku-lldikti.index', compact('sasaranList', 'triwulanList', 'triwulanDipilih'));
    }
}
