<?php

namespace App\Http\Controllers\Admin\Tools;

use App\Http\Controllers\Concerns\HandlesRestrictedDeletes;
use App\Http\Controllers\Controller;
use App\Models\TahunAnggaran;
use Illuminate\Http\Request;

class TahunAnggaranController extends Controller
{
    use HandlesRestrictedDeletes;

    // GET /admin/tools/tahun
    public function index(Request $request)
    {
        $tahunList = TahunAnggaran::when($request->filled('search'), fn ($q) => $q->where('tahun', 'like', '%'.$request->search.'%'))
            ->orderByDesc('tahun')
            ->paginate(15)
            ->withQueryString();

        return view('admin.tools.tahun.index', compact('tahunList'));
    }

    // POST /admin/tools/tahun (FR-23)
    public function store(Request $request)
    {
        $data = $request->validate([
            'tahun' => 'required|integer|digits:4|unique:tahun_anggaran,tahun',
        ], [
            'tahun.required' => 'Tahun wajib diisi.',
            'tahun.integer' => 'Tahun harus berupa angka.',
            'tahun.digits' => 'Tahun harus terdiri dari 4 digit.',
            'tahun.unique' => 'Tahun anggaran ini sudah ada.',
        ]);

        TahunAnggaran::create($data);

        return back()->with('feedback', ['type' => 'success', 'message' => 'Tahun Anggaran berhasil ditambahkan.']);
    }

    // DELETE /admin/tools/tahun/{id} (FR-24, FR-26: Confirmation Prompt wajib di frontend)
    public function destroy(TahunAnggaran $tahun)
    {
        return $this->deleteOrBlock(
            fn () => $tahun->delete(),
            'Tahun anggaran ini masih memiliki data Sasaran Kegiatan/Jumlah Mahasiswa/Jumlah PTS, tidak dapat dihapus.'
        );
    }
}
