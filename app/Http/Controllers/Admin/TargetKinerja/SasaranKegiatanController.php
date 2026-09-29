<?php

namespace App\Http\Controllers\Admin\TargetKinerja;

use App\Events\ActivityOccurred;
use App\Http\Controllers\Concerns\HandlesRestrictedDeletes;
use App\Http\Controllers\Concerns\ResolvesActiveTahunAnggaran;
use App\Http\Controllers\Controller;
use App\Models\Iku;
use App\Models\SasaranKegiatan;
use App\Models\TimKerja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SasaranKegiatanController extends Controller
{
    use HandlesRestrictedDeletes;
    use ResolvesActiveTahunAnggaran;

    // GET /admin/target-kinerja (FR-01)
    public function index(Request $request)
    {
        $this->authorize('viewAny', SasaranKegiatan::class);

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
        $this->authorize('create', SasaranKegiatan::class);

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

        $sasaran = SasaranKegiatan::create($data);

        event(new ActivityOccurred(
            subject: $sasaran,
            description: "membuat Sasaran Kegiatan \"{$sasaran->nama_sasaran}\"",
            causer: Auth::user(),
        ));

        return back()->with('feedback', ['type' => 'success', 'message' => 'Sasaran Kegiatan berhasil ditambahkan.']);
    }

    // PUT /admin/target-kinerja/{id} (FR-04)
    public function update(Request $request, SasaranKegiatan $sasaran)
    {
        $this->authorize('update', $sasaran);

        $data = $request->validate([
            'nama_sasaran' => 'required|string|max:255',
        ], [
            'nama_sasaran.required' => 'Nama Sasaran Kegiatan wajib diisi.',
            'nama_sasaran.max' => 'Nama Sasaran Kegiatan maksimal 255 karakter.',
        ]);
        $sasaran->update($data);

        event(new ActivityOccurred(
            subject: $sasaran,
            description: "memperbarui Sasaran Kegiatan \"{$sasaran->nama_sasaran}\"",
            causer: Auth::user(),
        ));

        return back()->with('feedback', ['type' => 'success', 'message' => 'Sasaran Kegiatan berhasil diperbarui.']);
    }

    // DELETE /admin/target-kinerja/{id} (block jika masih ada IKU anak — ERD D-5)
    public function destroy(SasaranKegiatan $sasaran)
    {
        $this->authorize('delete', $sasaran);

        return $this->deleteOrBlock(
            fn () => $sasaran->delete(),
            'Sasaran Kegiatan ini masih memiliki IKU, tidak dapat dihapus.'
        );
    }

    // GET /admin/target-kinerja/{id} -> halaman Detail (FR-05)
    public function show(SasaranKegiatan $sasaran, Request $request)
    {
        $this->authorize('show', $sasaran);

        $ikuList = Iku::with('timKerja')
            ->where('sasaran_kegiatan_id', $sasaran->id)
            ->when($request->filled('search'), fn ($q) => $q->where('deskripsi', 'like', '%'.$request->search.'%'))
            ->orderBy('kode')
            ->paginate(15)
            ->withQueryString();

        $timKerjaOptions = TimKerja::orderBy('nama_tim')->get(['id', 'nama_tim']);

        // REFAKTOR HYBRID: dropdown "Formula Perhitungan" (FormulaRegistry)
        // diganti dropdown "Tipe Capaian Kinerja" (tipe_iku, §3 spek). Tidak
        // ada prediksi otomatis berdasarkan nomor IKU (predictedFormulaKode
        // lama) karena tipe_iku murni pilihan Admin sesuai IKU resmi mana
        // yang sedang dibuat — tidak ada pemetaan 1:1 nomor->tipe yang aman
        // diasumsikan seperti formula lama.
        $tipeIkuOptions = config('capaian_kinerja_tipe');

        return view('admin.target-kinerja.iku.index', compact(
            'sasaran', 'ikuList', 'timKerjaOptions', 'tipeIkuOptions'
        ));
    }
}
