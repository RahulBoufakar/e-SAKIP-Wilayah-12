<?php

namespace App\Http\Controllers\Admin\Tools;

use App\Http\Controllers\Concerns\ResolvesActiveTahunAnggaran;
use App\Http\Controllers\Controller;
use App\Models\Triwulan;
use App\Models\TriwulanStatus;
use Illuminate\Http\Request;

class TriwulanController extends Controller
{
    use ResolvesActiveTahunAnggaran;

    // GET /admin/tools/triwulan (FR-19: tepat 4 baris tetap)
    public function index(Request $request)
    {
        $this->authorize('viewAny', Triwulan::class);

        $tahunAnggaranId = $this->activeTahunAnggaranId($request);
        if (! $tahunAnggaranId) {
            return $this->missingTahunAnggaran();
        }

        $triwulanStatusList = Triwulan::with(['statuses' => fn ($q) => $q->where('tahun_anggaran_id', $tahunAnggaranId)])
            ->orderBy('urutan')
            ->get();

        return view('admin.tools.triwulan.index', compact('triwulanStatusList', 'tahunAnggaranId'));
    }

    // PUT /admin/tools/triwulan/{id} (FR-20/FR-21: atomic single-active switch)
    public function update(Request $request, Triwulan $triwulan)
    {
        $this->authorize('activate', $triwulan);

        $data = $request->validate([
            'tahun_anggaran_id' => 'required|exists:tahun_anggaran,id',
        ], [
            'tahun_anggaran_id.required' => 'Tahun anggaran wajib dipilih.',
            'tahun_anggaran_id.exists' => 'Tahun anggaran tidak valid.',
        ]);

        TriwulanStatus::activate($triwulan->id, $data['tahun_anggaran_id']);

        return back()->with('feedback', ['type' => 'success', 'message' => "{$triwulan->kode} berhasil diaktifkan."]);
    }

    // PUT /admin/tools/triwulan/nonaktifkan-semua/{tahunAnggaranId} (FR-22: atomic non-active switch)
    public function nonaktifkanSemua(int $tahunAnggaranId)
    {
        $this->authorize('deactivateAll', Triwulan::class);

        // Panggil method static yang sudah diubah
        TriwulanStatus::activate(0, $tahunAnggaranId);
        
        return redirect()->back()->with('feedback', ['type' => 'success', 'message' => 'Semua triwulan berhasil dinonaktifkan.']);
    }
}
