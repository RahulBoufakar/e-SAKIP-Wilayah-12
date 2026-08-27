<?php

namespace App\Http\Controllers\Admin;

use App\Formulas\FormulaRegistry;
use App\Http\Controllers\Concerns\HandlesRestrictedDeletes;
use App\Http\Controllers\Concerns\ResolvesActiveTahunAnggaran;
use App\Http\Controllers\Controller;
use App\Models\Iku;
use App\Models\SasaranKegiatan;
use App\Models\TimKerja;
use Illuminate\Http\Request;

class SasaranKegiatanController extends Controller
{
    use HandlesRestrictedDeletes;
    use ResolvesActiveTahunAnggaran;

    // GET /admin/target-kinerja (FR-01)
    public function index(Request $request)
    {
        $tahunAnggaranId = $this->activeTahunAnggaranId($request);
        if (! $tahunAnggaranId) {
            return $this->missingTahunAnggaran();
        }

        $sasaranList = SasaranKegiatan::where('tahun_anggaran_id', $tahunAnggaranId)
            ->when($request->filled('search'), fn ($q) => $q->where('nama_sasaran', 'like', '%'.$request->search.'%'))
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.target-kinerja.index', compact('sasaranList'));
    }

    // POST /admin/target-kinerja (FR-02, FR-03: kode auto-generate di model)
    public function store(Request $request)
    {
        $tahunAnggaranId = $this->activeTahunAnggaranId($request);
        if (! $tahunAnggaranId) {
            return back()->with('feedback', ['type' => 'error', 'message' => 'Tahun anggaran belum tersedia.']);
        }

        $data = $request->validate([
            'nama_sasaran' => 'required|string|max:255',
        ], [
            'nama_sasaran.required' => 'Nama Sasaran Kegiatan wajib diisi.',
            'nama_sasaran.max' => 'Nama Sasaran Kegiatan maksimal 255 karakter.',
        ]);
        $data['tahun_anggaran_id'] = $tahunAnggaranId;

        SasaranKegiatan::create($data);

        return back()->with('feedback', ['type' => 'success', 'message' => 'Sasaran Kegiatan berhasil ditambahkan.']);
    }

    // PUT /admin/target-kinerja/{id} (FR-04)
    public function update(Request $request, SasaranKegiatan $sasaran)
    {
        $data = $request->validate([
            'nama_sasaran' => 'required|string|max:255',
        ], [
            'nama_sasaran.required' => 'Nama Sasaran Kegiatan wajib diisi.',
            'nama_sasaran.max' => 'Nama Sasaran Kegiatan maksimal 255 karakter.',
        ]);
        $sasaran->update($data);

        return back()->with('feedback', ['type' => 'success', 'message' => 'Sasaran Kegiatan berhasil diperbarui.']);
    }

    // DELETE /admin/target-kinerja/{id} (block jika masih ada IKU anak — ERD D-5)
    public function destroy(SasaranKegiatan $sasaran)
    {
        return $this->deleteOrBlock(
            fn () => $sasaran->delete(),
            'Sasaran Kegiatan ini masih memiliki IKU, tidak dapat dihapus.'
        );
    }

    // GET /admin/target-kinerja/{id} -> halaman Detail (FR-05)
    public function show(SasaranKegiatan $sasaran, Request $request)
    {
        $ikuList = Iku::with('timKerja')
            ->where('sasaran_kegiatan_id', $sasaran->id)
            ->when($request->filled('search'), fn ($q) => $q->where('deskripsi', 'like', '%'.$request->search.'%'))
            ->orderBy('kode')
            ->paginate(15)
            ->withQueryString();

        $timKerjaOptions = TimKerja::orderBy('nama_tim')->get(['id', 'nama_tim']);
        $formulaOptions = FormulaRegistry::list();

        // Prediksi nomor yang akan dipakai model saat IKU baru disimpan (lihat
        // Iku::booted()), supaya dropdown formula bisa auto-terpilih sebelum submit.
        $nomorSasaran = (int) str_replace('s.', '', $sasaran->kode);
        $urutanBerikutnya = Iku::where('sasaran_kegiatan_id', $sasaran->id)->count() + 1;
        $predictedFormulaKode = FormulaRegistry::resolveByNomor("{$nomorSasaran}.{$urutanBerikutnya}");

        return view('admin.target-kinerja.iku.index', compact(
            'sasaran', 'ikuList', 'timKerjaOptions', 'formulaOptions', 'predictedFormulaKode'
        ));
    }
}
