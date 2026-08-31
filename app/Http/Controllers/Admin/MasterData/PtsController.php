<?php

namespace App\Http\Controllers\Admin\MasterData;

use App\Http\Controllers\Concerns\HandlesRestrictedDeletes;
use App\Http\Controllers\Controller;
use App\Models\Pts;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PtsController extends Controller
{
    use HandlesRestrictedDeletes;
    // GET /admin/master-data/pts
    public function index(Request $request)
    {
        $this->authorize('viewAny', Pts::class);

        $ptsList = Pts::when($request->filled('search'), function ($q) use ($request) {
                $q->where('nama_pts', 'like', '%'.$request->search.'%')
                    ->orWhere('kode_pts', 'like', '%'.$request->search.'%');
            })
            ->orderBy('nama_pts')
            ->paginate(15)
            ->withQueryString();

        return view('admin.master-data.pts.index', compact('ptsList'));
    }

    // POST /admin/master-data/pts
    public function store(Request $request)
    {
        $this->authorize('create', Pts::class);

        $data = $this->validated($request);
        Pts::create($data);

        return back()->with('feedback', ['type' => 'success', 'message' => 'Data PTS berhasil ditambahkan.']);
    }

    // PUT /admin/master-data/pts/{pts}
    public function update(Request $request, Pts $pts)
    {
        $this->authorize('update', $pts);

        $data = $this->validated($request, $pts);
        $pts->update($data);

        return back()->with('feedback', ['type' => 'success', 'message' => 'Data PTS berhasil diperbarui.']);
    }

    // DELETE /admin/master-data/pts/{pts}
    public function destroy(Pts $pts)
    {
        $this->authorize('delete', $pts);

        return $this->deleteOrBlock(
            fn () => $pts->delete(),
            'Data PTS ini masih ditagging pada Program Kerja, tidak dapat dihapus.'
        );
    }

    private function validated(Request $request, ?Pts $pts = null): array
    {
        return $request->validate([
            'kode_pts' => ['required', 'string', 'max:20', Rule::unique('pts', 'kode_pts')->ignore($pts?->id)],
            'nama_pts' => 'required|string|max:255',
            'status_pts' => 'required|in:aktif,alih_bentuk,tutup,alih_kelola,pembinaan',
            'akreditasi_pts' => 'nullable|in:unggul,terakreditasi,tidak_terakreditasi',
        ], [
            'kode_pts.required' => 'Kode PTS wajib diisi.',
            'kode_pts.max' => 'Kode PTS maksimal 20 karakter.',
            'kode_pts.unique' => 'Kode PTS sudah digunakan.',
            'nama_pts.required' => 'Nama PTS wajib diisi.',
            'nama_pts.max' => 'Nama PTS maksimal 255 karakter.',
            'status_pts.required' => 'Status PTS wajib dipilih.',
            'status_pts.in' => 'Status PTS tidak valid.',
            'akreditasi_pts.in' => 'Akreditasi PTS tidak valid.',
        ]);
    }
}