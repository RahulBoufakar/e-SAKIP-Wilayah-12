<?php

namespace App\Http\Controllers\Admin\Tools;

use App\Events\ActivityOccurred;
use App\Http\Controllers\Controller;
use App\Models\JumlahPts;
use App\Models\TahunAnggaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JumlahPtsController extends Controller
{
    // GET /admin/tools/jumlah-pts
    public function index()
    {
        $this->authorize('viewAny', JumlahPts::class);

        $data = JumlahPts::with('tahunAnggaran')->orderByDesc('id')->paginate(15);
        $tahunOptions = TahunAnggaran::orderByDesc('tahun')->get(['id', 'tahun']);

        return view('admin.tools.jumlah-pts.index', compact('data', 'tahunOptions'));
    }

    // POST /admin/tools/jumlah-pts (FR-31)
    public function store(Request $request)
    {
        $this->authorize('create', JumlahPts::class);

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
        $jumlahPts = JumlahPts::create($data);

        // AUDIT § A4: sebelumnya mutasi Master Data ini belum tercatat sama sekali.
        $tahun = TahunAnggaran::find($data['tahun_anggaran_id'])?->tahun;
        event(new ActivityOccurred(
            subject: $jumlahPts,
            description: "menambahkan data Jumlah PTS TA {$tahun}: {$data['jumlah']}",
            causer: Auth::user(),
        ));

        return back()->with('feedback', ['type' => 'success', 'message' => 'Jumlah PTS berhasil ditambahkan.']);
    }

    // DELETE /admin/tools/jumlah-pts/{id} (FR-31/FR-32: Confirmation Prompt wajib di frontend)
    public function destroy(JumlahPts $jumlahPts)
    {

        $this->authorize('delete', $jumlahPts);

        // AUDIT § A4: snapshot sebelum delete.
        $tahun = $jumlahPts->tahunAnggaran?->tahun;
        $jumlahSebelum = $jumlahPts->jumlah;

        $jumlahPts->delete();

        event(new ActivityOccurred(
            subject: $jumlahPts,
            description: "menghapus data Jumlah PTS TA {$tahun}: {$jumlahSebelum}",
            causer: Auth::user(),
        ));

        return back()->with('feedback', ['type' => 'success', 'message' => 'Data Jumlah PTS berhasil dihapus.']);
    }
}
