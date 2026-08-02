<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesRestrictedDeletes;
use App\Http\Controllers\Controller;
use App\Models\Iku;
use App\Models\SasaranKegiatan;
use App\Models\TimKerja;
use Illuminate\Http\Request;

class IkuController extends Controller
{
    use HandlesRestrictedDeletes;

    // GET /admin/iku — tampilkan semua IKU & IKK yang sudah ada
    public function index(Request $request)
    {
        $listIku = Iku::with(['sasaranKegiatan', 'timKerja'])
            ->when($request->filled('search'), fn ($q) => $q->where('deskripsi', 'like', '%'.$request->search.'%'))
            ->orderBy('kode')
            ->paginate(15)
            ->withQueryString();

        $sasaran = SasaranKegiatan::orderBy('kode')->get(['id', 'kode']);
        $timKerjaOptions = TimKerja::orderBy('nama_tim')->get(['id', 'nama_tim']);
        return view('admin.target-kinerja.iku.index', compact('listIku', 'sasaran', 'timKerjaOptions'));
    }

    // POST /admin/iku — buat IKU atau IKK, dibedakan field 'jenis' pada form
    public function store(Request $request)
    {
        $jenis = $request->validate([
            'jenis' => 'required|in:IKU,IKK',
        ], [
            'jenis.required' => 'Jenis wajib dipilih.',
            'jenis.in' => 'Jenis tidak valid.',
        ])['jenis'];

        $data = $request->validate([
            'sasaran_kegiatan_id' => 'required|exists:sasaran_kegiatan,id',
            'deskripsi' => 'required|string',
            'target_pk' => 'required|numeric|min:1',
            'satuan' => 'required|string|max:20',
            'deskripsi_target' => 'nullable|string|max:255',
            'tim_kerja_id' => 'nullable|exists:tim_kerja,id',
        ], [
            'sasaran_kegiatan_id.required' => 'Sasaran Kegiatan wajib dipilih.',
            'sasaran_kegiatan_id.exists' => 'Sasaran Kegiatan tidak valid.',
            'deskripsi.required' => 'Deskripsi wajib diisi.',
            'target_pk.required' => 'Target wajib diisi.',
            'target_pk.numeric' => 'Target harus berupa angka.',
            'target_pk.min' => 'Target tidak boleh negatif dan minimal 1.',
            'satuan.required' => 'Satuan wajib diisi.',
            'satuan.max' => 'Satuan tidak boleh lebih dari 20 karakter.',
            'deskripsi_target.max' => 'Deskripsi Target tidak boleh lebih dari 255 karakter.',
            'tim_kerja_id.exists' => 'Tim Kerja tidak valid.',
        ]);
        $data['jenis'] = $jenis;
        Iku::create($data);

        $label = $jenis === 'IKK' ? 'IKK' : 'IKU';
        return back()->with('feedback', ['type' => 'success', 'message' => "{$label} berhasil ditambahkan."]);
    }

    // PUT /admin/iku/{iku} — update IKU maupun IKK (jenis & sasaran_kegiatan_id tidak berubah)
    public function update(Request $request, Iku $iku)
    {
        $jenis = $request->validate([
            'jenis' => 'required|in:IKU,IKK',
        ], [
            'jenis.required' => 'Jenis wajib dipilih.',
            'jenis.in' => 'Jenis tidak valid.',
        ])['jenis'];

        if ($jenis !== $iku->jenis) {
            return back()->withErrors(['jenis' => 'Jenis tidak dapat diubah.'])->withInput();
        }

        $data = $request->validate([
            'deskripsi' => 'required|string',
            'target_pk' => 'required|numeric|min:1',
            'satuan' => 'required|string|max:20',
            'tim_kerja_id' => 'nullable|exists:tim_kerja,id',
            'deskripsi_target' => 'nullable|string|max:255',
        ], [
            'deskripsi.required' => 'Deskripsi wajib diisi.',
            'target_pk.required' => 'Target wajib diisi.',
            'target_pk.numeric' => 'Target harus berupa angka.',
            'target_pk.min' => 'Target tidak boleh negatif dan minimal 1.',
            'satuan.required' => 'Satuan wajib diisi.',
            'satuan.max' => 'Satuan tidak boleh lebih dari 20 karakter.',
            'deskripsi_target.max' => 'Deskripsi Target tidak boleh lebih dari 255 karakter.',
            'tim_kerja_id.exists' => 'Tim Kerja tidak valid.',
        ]);
        $iku->update($data);

        $label = $iku->jenis === 'IKK' ? 'IKK' : 'IKU';
        return back()->with('feedback', ['type' => 'success', 'message' => "{$label} berhasil diperbarui."]);
    }

    // DELETE /admin/iku/{iku}
    public function destroy(Iku $iku)
    {
        $label = $iku->jenis === 'IKK' ? 'IKK' : 'IKU';
        return $this->deleteOrBlock(
            fn () => $iku->delete(),
            "{$label} ini masih memiliki Rencana Aksi, tidak dapat dihapus."
        );
    }
}