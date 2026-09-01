<?php

namespace App\Http\Controllers\Admin\Tools;

use App\Http\Controllers\Controller;
use App\Models\JumlahMahasiswa;
use App\Models\TahunAnggaran;
use Illuminate\Http\Request;

class JumlahMahasiswaController extends Controller
{
    // GET /admin/tools/jumlah-mahasiswa
    public function index()
    {
        $this->authorize('viewAny', JumlahMahasiswa::class);

        $data = JumlahMahasiswa::with('tahunAnggaran')->orderByDesc('id')->paginate(15);
        $tahunOptions = TahunAnggaran::orderByDesc('tahun')->get(['id', 'tahun']);

        return view('admin.tools.jumlah-mahasiswa.index', compact('data', 'tahunOptions'));
    }

    // POST /admin/tools/jumlah-mahasiswa (FR-28)
    public function store(Request $request)
    {
        $this->authorize('create', JumlahMahasiswa::class);

        $data = $request->validate([
            'tahun_anggaran_id' => 'required|exists:tahun_anggaran,id',
            'jumlah' => 'required|integer|min:0',
        ], [
            'tahun_anggaran_id.required' => 'Tahun anggaran wajib dipilih.',
            'tahun_anggaran_id.exists' => 'Tahun anggaran tidak valid.',
            'jumlah.required' => 'Jumlah wajib diisi.',
            'jumlah.integer' => 'Jumlah harus berupa angka bulat.',
            'jumlah.min' => 'Jumlah tidak boleh negatif.',
        ]);

        // FR-30: tidak memicu recalculation apa pun
        JumlahMahasiswa::create($data);

        return back()->with('feedback', ['type' => 'success', 'message' => 'Jumlah Mahasiswa berhasil ditambahkan.']);
    }

    // DELETE /admin/tools/jumlah-mahasiswa/{id} (FR-28/FR-29: Confirmation Prompt wajib di frontend)
    public function destroy(JumlahMahasiswa $jumlahMahasiswa)
    {
        $this->authorize('delete', JumlahMahasiswa::class);

        $jumlahMahasiswa->delete();

        return back()->with('feedback', ['type' => 'success', 'message' => 'Data Jumlah Mahasiswa berhasil dihapus.']);
    }
}
