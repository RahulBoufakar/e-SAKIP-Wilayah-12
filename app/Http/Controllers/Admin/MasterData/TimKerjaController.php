<?php

namespace App\Http\Controllers\Admin\MasterData;

use App\Http\Controllers\Concerns\HandlesRestrictedDeletes;
use App\Http\Controllers\Controller;
use App\Models\TimKerja;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TimKerjaController extends Controller
{
    use HandlesRestrictedDeletes;

    // GET /admin/master-data/tim-kerja (FR-M1)
    public function index(Request $request)
    {
        $this->authorize('viewAny', TimKerja::class);

        $timKerja = TimKerja::when($request->filled('search'), fn ($q) => $q->where('nama_tim', 'like', '%'.$request->search.'%'))
            ->orderBy('nama_tim')
            ->paginate(15)
            ->withQueryString();

        return view('admin.master-data.tim-kerja.index', compact('timKerja'));
    }

    // POST /admin/master-data/tim-kerja (FR-M1)
    public function store(Request $request)
    {
        $this->authorize('create', TimKerja::class);

        $data = $request->validate([
            'nama_tim' => 'required|string|max:100|unique:tim_kerja,nama_tim',
        ], [
            'nama_tim.required' => 'Nama Tim Kerja wajib diisi.',
            'nama_tim.max' => 'Nama Tim Kerja maksimal 100 karakter.',
            'nama_tim.unique' => 'Nama Tim Kerja sudah digunakan.',
        ]);
        TimKerja::create($data);

        return back()->with('feedback', ['type' => 'success', 'message' => 'Tim Kerja berhasil ditambahkan.']);
    }

    // PUT /admin/master-data/tim-kerja/{id} (FR-M1)
    public function update(Request $request, TimKerja $timKerja)
    {
        $this->authorize('update', $timKerja);

        $data = $request->validate([
            'nama_tim' => ['required', 'string', 'max:100', Rule::unique('tim_kerja', 'nama_tim')->ignore($timKerja->id)],
        ], [
            'nama_tim.required' => 'Nama Tim Kerja wajib diisi.',
            'nama_tim.max' => 'Nama Tim Kerja maksimal 100 karakter.',
            'nama_tim.unique' => 'Nama Tim Kerja sudah digunakan.',
        ]);
        $timKerja->update($data);

        return back()->with('feedback', ['type' => 'success', 'message' => 'Tim Kerja berhasil diperbarui.']);
    }

    // DELETE /admin/master-data/tim-kerja/{id} (FR-M2: block jika dipakai users/ikk — D-6)
    public function destroy(TimKerja $timKerja)
    {
        $this->authorize('delete', $timKerja);

        return $this->deleteOrBlock(
            fn () => $timKerja->delete(),
            'Tim Kerja ini masih digunakan oleh User atau IKK, tidak dapat dihapus.'
        );
    }
}
