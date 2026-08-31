<?php

namespace App\Http\Controllers\Admin\TargetKinerja;

use App\Http\Controllers\Concerns\ResolvesActiveTahunAnggaran;
use App\Http\Controllers\Controller;
use App\Models\Iku;
use App\Models\RencanaAksi;
use App\Models\Triwulan;
use Illuminate\Http\Request;

class RencanaAksiController extends Controller
{
    use ResolvesActiveTahunAnggaran;

    // GET /admin/rencana-aksi (FR-15). Catatan: GET /admin/rencana-aksi/{iku}
    // (pre-fill via fetch) DIHAPUS — sekarang uraian existing dikirim langsung
    // via eager-load 'rencanaAksi' & dirender inline oleh Blade (PRD §6.1/6.2:
    // Alpine tidak boleh fetch data).
    public function index(Request $request)
    {
        $this->authorize('viewAny', Iku::class);

        $tahunAnggaranId = $this->activeTahunAnggaranId($request);
        if (! $tahunAnggaranId) {
            return $this->missingTahunAnggaran();
        }

        $triwulanList = Triwulan::orderBy('urutan')->get();

        $ikuList = Iku::with('rencanaAksi')
            ->whereHas('sasaranKegiatan', fn ($q) => $q->where('tahun_anggaran_id', $tahunAnggaranId))
            ->when($request->filled('search'), fn ($q) => $q->where('deskripsi', 'like', '%'.$request->search.'%'))
            ->orderBy('kode')
            ->paginate(15)
            ->withQueryString();

        return view('admin.target-kinerja.rencana-aksi.index', compact('ikuList', 'triwulanList', 'tahunAnggaranId'));
    }

    // PUT /admin/rencana-aksi/{iku} (FR-16/FR-17 — Rule R-4: tanpa gate Triwulan Aktif)
    // Field form: uraian[{triwulan_id}] agar tidak bergantung asumsi id 1-4.
    public function update(Request $request, Iku $iku)
    {
        $this->authorize('manageRencanaAksi', $iku);

        $data = $request->validate([
            'uraian' => 'nullable|array',
            'uraian.*' => 'nullable|string',
        ], [
            'uraian.*.string' => 'Uraian rencana aksi harus berupa teks.',
        ]);

        $triwulanList = Triwulan::orderBy('urutan')->get();
        $tahunAnggaranId = $iku->sasaranKegiatan->tahun_anggaran_id;

        foreach ($triwulanList as $tw) {
            RencanaAksi::updateOrCreate(
                [
                    'iku_id' => $iku->id,
                    'triwulan_id' => $tw->id,
                    'tahun_anggaran_id' => $tahunAnggaranId,
                ],
                ['uraian' => $data['uraian'][$tw->id] ?? null]
            );
        }

        return back()->with('feedback', ['type' => 'success', 'message' => 'Rencana Aksi berhasil disimpan.']);
    }
}
