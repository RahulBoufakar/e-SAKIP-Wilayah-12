<?php

namespace App\Http\Controllers\TimKerja\CapaianKinerja;

use App\Events\ActivityOccurred;
use App\Formulas\FormulaRegistry;
use App\Http\Controllers\Concerns\ResolvesTimKerjaSession;
use App\Http\Controllers\Controller;
use App\Models\CapaianKinerja;
use App\Models\Iku;
use App\Models\Triwulan;
use App\Models\TriwulanStatus;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

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

    public function show(Request $request, Iku $iku, Triwulan $triwulan)
    {
        $tahunAnggaranId = $this->activeTahunAnggaranId($request);
        if (! $tahunAnggaranId) {
            return $this->missingTahunAnggaran('tim-kerja.layout.app', 'tim-kerja.dashboard');
        }

        $this->authorize('manageKinerja', $iku);

        $capaian = CapaianKinerja::firstOrCreate([
            'iku_id' => $iku->id,
            'triwulan_id' => $triwulan->id,
            'tahun_anggaran_id' => $tahunAnggaranId,
        ]);
        $capaian->load('dokumen');

        $formula = FormulaRegistry::resolve($iku->formula_kode);

        return view('tim-kerja.capaian-kinerja.show', compact('iku', 'triwulan', 'capaian', 'formula'));
    }

    // PUT /tim-kerja/capaian-kinerja/{iku}/{triwulan}
    public function update(Request $request, Iku $iku, Triwulan $triwulan)
    {
        $tahunAnggaranId = $this->activeTahunAnggaranId($request);
        $this->authorize('manageKinerja', $iku);

        $capaian = CapaianKinerja::firstOrCreate([
            'iku_id' => $iku->id,
            'triwulan_id' => $triwulan->id,
            'tahun_anggaran_id' => $tahunAnggaranId,
        ]);

        if ($capaian->isFieldLocked()) {
            return back()->with('feedback', ['type' => 'error', 'message' => 'Data ini sedang terkunci dan tidak dapat diubah.']);
        }

        $formula = FormulaRegistry::resolve($iku->formula_kode);

        if ($formula) {
            $rules = collect($formula->variables())
                ->mapWithKeys(fn ($v) => ["variabel.{$v['key']}" => 'required|numeric|min:0'])
                ->all();
            $data = $request->validate($rules);

            $capaian->simpan([
                'variabel' => $data['variabel'],
                'realisasi' => $formula->calculate($data['variabel']),
            ]);
        } else {
            $data = $request->validate(['realisasi' => 'required|numeric|min:0'], [
                'realisasi.required' => 'Realisasi wajib diisi.',
            ]);
            $capaian->simpan($data);
        }

        event(new ActivityOccurred(
            subject: $capaian,
            description: "menyimpan draft Capaian Kinerja IKU {$iku->kode} — {$triwulan->kode}",
            causer: Auth::user(),
        ));

        return back()->with('feedback', ['type' => 'success', 'message' => 'Capaian berhasil disimpan.']);
    }

    // PUT /tim-kerja/capaian-kinerja/{capaianKinerja}/kirim
    public function kirim(CapaianKinerja $capaianKinerja)
    {
        $this->authorize('manageKinerja', $capaianKinerja->iku);

        if (! $capaianKinerja->isDataLengkap()) {
            return back()->with('feedback', ['type' => 'error', 'message' => 'Lengkapi seluruh nilai variabel/realisasi sebelum mengirim untuk validasi.']);
        }

        if ($capaianKinerja->dokumen()->count() < 1) {
            return back()->with('feedback', ['type' => 'error', 'message' => 'Lampirkan minimal 1 dokumen bukti sebelum mengirim untuk validasi.']);
        }

        try {
            $capaianKinerja->kirim();
        } catch (\RuntimeException $e) {
            return back()->with('feedback', ['type' => 'error', 'message' => $e->getMessage()]);
        }

        event(new ActivityOccurred(
            subject: $capaianKinerja,
            description: "mengirim Capaian Kinerja IKU {$capaianKinerja->iku->kode} — {$capaianKinerja->triwulan->kode} untuk validasi",
            causer: Auth::user(),
            recipients: User::role('validator')->get(),
            url: route('validator.capaian-kinerja.show', [$capaianKinerja->iku_id, $capaianKinerja->triwulan_id]),
        ));

        return back()->with('feedback', ['type' => 'success', 'message' => 'Capaian Kinerja berhasil dikirim untuk validasi.']);
    }
}