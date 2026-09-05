<?php

namespace App\Http\Controllers\Admin\TargetKinerja;

use App\Events\ActivityOccurred;
use App\Formulas\FormulaRegistry;
use App\Http\Controllers\Concerns\HandlesRestrictedDeletes;
use App\Http\Controllers\Controller;
use App\Models\Iku;
use App\Models\SasaranKegiatan;
use App\Models\TimKerja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class IkuController extends Controller
{
    use HandlesRestrictedDeletes;

    // POST /admin/iku — buat IKU atau IKK, dibedakan field 'jenis' pada form
    public function store(Request $request)
    {
        $this->authorize('create', Iku::class);

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
            'formula_kode' => ['nullable', Rule::in(FormulaRegistry::keys())],
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
            'formula_kode.in' => 'Formula tidak valid.',
        ]);
        $data['jenis'] = $jenis;
        $iku = Iku::create($data);

        $label = $jenis === 'IKK' ? 'IKK' : 'IKU';

        event(new ActivityOccurred(
            subject: $iku,
            description: "membuat {$label} baru {$iku->kode} — {$iku->deskripsi}",
            causer: Auth::user(),
        ));

        return back()->with('feedback', ['type' => 'success', 'message' => "{$label} berhasil ditambahkan."]);
    }

    // PUT /admin/iku/{iku} — update IKU maupun IKK (jenis & sasaran_kegiatan_id tidak berubah)
    public function update(Request $request, Iku $iku)
    {
        $this->authorize('update', Iku::class);

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
            'formula_kode' => ['nullable', Rule::in(FormulaRegistry::keys())],
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
            'formula_kode.in' => 'Formula tidak valid.',
        ]);
        $iku->update($data);

        $label = $iku->jenis === 'IKK' ? 'IKK' : 'IKU';

        event(new ActivityOccurred(
            subject: $iku,
            description: "memperbarui {$label} {$iku->kode} — {$iku->deskripsi}",
            causer: Auth::user(),
        ));

        return back()->with('feedback', ['type' => 'success', 'message' => "{$label} berhasil diperbarui."]);
    }

    // DELETE /admin/iku/{iku}
    public function destroy(Iku $iku)
    {
        $this->authorize('delete', Iku::class);

        $label = $iku->jenis === 'IKK' ? 'IKK' : 'IKU';
        return $this->deleteOrBlock(
            fn () => $iku->delete(),
            "{$label} ini masih memiliki Rencana Aksi, tidak dapat dihapus."
        );
    }
}