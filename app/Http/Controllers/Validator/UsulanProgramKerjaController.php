<?php

namespace App\Http\Controllers\Validator;

use App\Http\Controllers\Controller;
use App\Models\UsulanProgramKerja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class UsulanProgramKerjaController extends Controller
{
    private const STATUS_VALID = ['menunggu_validasi', 'approved', 'rejected'];

    // GET /validator/usulan-program-kerja?status=menunggu_validasi
    public function index(Request $request)
    {
        $status = in_array($request->get('status'), self::STATUS_VALID, true)
            ? $request->get('status')
            : 'menunggu_validasi';

        $usulanList = UsulanProgramKerja::with(['iku.timKerja'])
            ->where('status_validasi', $status)
            ->orderByDesc('tgl_validasi')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('validator.usulan-program-kerja.index', compact('usulanList', 'status'));
    }

    // GET /validator/usulan-program-kerja/{usulanProgramKerja}
    public function show(UsulanProgramKerja $usulanProgramKerja)
    {
        $usulanProgramKerja->load(['iku.timKerja', 'detailKegiatan', 'validator']);

        $bulanIndo = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        return view('validator.usulan-program-kerja.show', [
            'usulan' => $usulanProgramKerja,
            'bulanIndo' => $bulanIndo,
        ]);
    }

    // PUT /validator/usulan-program-kerja/{usulanProgramKerja}/setujui
    public function setujui(UsulanProgramKerja $usulanProgramKerja)
    {
        try {
            $usulanProgramKerja->setujui(Auth::id());
        } catch (RuntimeException $e) {
            return back()->with('feedback', ['type' => 'error', 'message' => $e->getMessage()]);
        }

        return back()->with('feedback', ['type' => 'success', 'message' => 'Usulan Program Kerja disetujui.']);
    }

    // PUT /validator/usulan-program-kerja/{usulanProgramKerja}/tolak
    public function tolak(Request $request, UsulanProgramKerja $usulanProgramKerja)
    {
        $data = $request->validate([
            'catatan_revisi' => 'required|string',
        ], [
            'catatan_revisi.required' => 'Catatan revisi wajib diisi saat menolak usulan.',
        ]);

        try {
            $usulanProgramKerja->tolak(Auth::id(), $data['catatan_revisi']);
        } catch (RuntimeException $e) {
            return back()->with('feedback', ['type' => 'error', 'message' => $e->getMessage()]);
        }

        return back()->with('feedback', ['type' => 'success', 'message' => 'Usulan Program Kerja ditolak.']);
    }
}