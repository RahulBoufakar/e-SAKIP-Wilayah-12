<?php

namespace App\Http\Controllers\Validator\CapaianKinerja;

use App\Formulas\FormulaRegistry;
use App\Http\Controllers\Concerns\ResolvesActiveTahunAnggaran;
use App\Http\Controllers\Controller;
use App\Models\CapaianKinerja;
use App\Models\Iku;
use App\Models\Triwulan;
use App\Models\TriwulanStatus;
use Illuminate\Http\Request;
use InvalidArgumentException;
use RuntimeException;

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

    // GET /validator/capaian-kinerja/{iku}/{triwulan}
    public function show(Request $request, Iku $iku, Triwulan $triwulan)
    {
        $tahunAnggaranId = $this->activeTahunAnggaranId($request);
        if (! $tahunAnggaranId) {
            return $this->missingTahunAnggaran('validator.layout.app', 'validator.dashboard');
        }

        $capaian = CapaianKinerja::with('dokumen')
            ->where('iku_id', $iku->id)
            ->where('triwulan_id', $triwulan->id)
            ->where('tahun_anggaran_id', $tahunAnggaranId)
            ->first();

        $formula = FormulaRegistry::resolve($iku->formula_kode);

        return view('validator.capaian-kinerja.show', compact('iku', 'triwulan', 'capaian', 'formula'));
    }

    // PUT /validator/capaian-kinerja/{capaianKinerja}/setujui
    public function setujui(CapaianKinerja $capaianKinerja)
    {
        try {
            $capaianKinerja->setujui();
        } catch (RuntimeException $e) {
            return back()->with('feedback', ['type' => 'error', 'message' => $e->getMessage()]);
        }

        return back()->with('feedback', ['type' => 'success', 'message' => 'Capaian Kinerja disetujui.']);
    }

    // PUT /validator/capaian-kinerja/{capaianKinerja}/tolak
    public function tolak(Request $request, CapaianKinerja $capaianKinerja)
    {
        $data = $request->validate([
            'catatan_revisi' => 'required|string',
        ], [
            'catatan_revisi.required' => 'Catatan revisi wajib diisi saat menolak.',
        ]);

        try {
            $capaianKinerja->tolak($data['catatan_revisi']);
        } catch (RuntimeException|InvalidArgumentException $e) {
            return back()->with('feedback', ['type' => 'error', 'message' => $e->getMessage()]);
        }

        return back()->with('feedback', ['type' => 'success', 'message' => 'Capaian Kinerja ditolak.']);
    }
}