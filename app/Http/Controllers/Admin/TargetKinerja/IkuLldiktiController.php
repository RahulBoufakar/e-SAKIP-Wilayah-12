<?php

namespace App\Http\Controllers\Admin\TargetKinerja;

use App\Http\Controllers\Controller;
use App\Models\CapaianKinerja;
use App\Models\Iku;
use App\Models\SasaranKegiatan;
use App\Models\TahunAnggaran;
use App\Models\Triwulan;
use App\Models\TriwulanStatus;
use Illuminate\Http\Request;

class IkuLldiktiController extends Controller
{
    // GET /admin/iku-lldikti — Admin boleh pilih & edit Target di Triwulan mana pun
    public function index(Request $request)
    {
        $this->authorize('viewAny', Iku::class);

        $tahunAnggaranId = $request->session()->get('tahun_anggaran_id')
            ?: optional(TahunAnggaran::orderByDesc('tahun')->first())->id;

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

        return view('admin.target-kinerja.iku-lldikti.index', compact('sasaranList', 'triwulanList', 'triwulanDipilih', 'tahunAnggaranId'));
    }

    // PUT /admin/capaian-kinerja/target
    // Admin boleh mengubah Target untuk Triwulan mana pun, tidak dibatasi
    // hanya Triwulan yang sedang aktif.
    public function updateTarget(Request $request)
    {
        $this->authorize('manageTarget', Iku::class);

        $data = $request->validate([
            'iku_id' => 'required|exists:iku,id',
            'triwulan_id' => 'required|exists:triwulan,id',
            'tahun_anggaran_id' => 'required|exists:tahun_anggaran,id',
            'target' => 'nullable|numeric|min:0',
        ], [
            'iku_id.required' => 'IKU wajib dipilih.',
            'iku_id.exists' => 'IKU tidak valid.',
            'triwulan_id.required' => 'Triwulan wajib dipilih.',
            'triwulan_id.exists' => 'Triwulan tidak valid.',
            'tahun_anggaran_id.required' => 'Tahun anggaran wajib dipilih.',
            'tahun_anggaran_id.exists' => 'Tahun anggaran tidak valid.',
            'target.numeric' => 'Target harus berupa angka.',
        ]);

        CapaianKinerja::updateOrCreate(
            [
                'iku_id' => $data['iku_id'],
                'triwulan_id' => $data['triwulan_id'],
                'tahun_anggaran_id' => $data['tahun_anggaran_id'],
            ],
            ['target' => $data['target'] ?? null]
        );

        return back()->with('feedback', ['type' => 'success', 'message' => 'Target berhasil disimpan.']);
    }
}