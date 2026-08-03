<?php

namespace App\Http\Controllers\Admin\Tools;

use App\Http\Controllers\Controller;
use App\Models\JumlahPts;
use App\Models\TahunAnggaran;
use Illuminate\Http\Request;

class JumlahPtsController extends Controller
{
    // GET /admin/tools/jumlah-pts
    public function index()
    {
        $data = JumlahPts::with('tahunAnggaran')->orderByDesc('id')->paginate(15);
        $tahunOptions = TahunAnggaran::orderByDesc('tahun')->get(['id', 'tahun']);

        return view('admin.tools.jumlah-pts.index', compact('data', 'tahunOptions'));
    }

    // POST /admin/tools/jumlah-pts (FR-31)
    public function store(Request $request)
    {
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

        // FR-33: tidak memicu recalculation apa pun
        JumlahPts::create($data);

        return back()->with('feedback', ['type' => 'success', 'message' => 'Jumlah PTS berhasil ditambahkan.']);
    }

    // DELETE /admin/tools/jumlah-pts/{id} (FR-31/FR-32: Confirmation Prompt wajib di frontend)
    public function destroy(JumlahPts $jumlahPts)
    {
        $jumlahPts->delete();

        return back()->with('feedback', ['type' => 'success', 'message' => 'Data Jumlah PTS berhasil dihapus.']);
    }
}
