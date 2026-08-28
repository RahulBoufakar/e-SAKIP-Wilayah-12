<?php

namespace App\Http\Controllers\TimKerja\ProgramKerja;

use App\Http\Controllers\Concerns\GatesUsulanProgramKerja;
use App\Http\Controllers\Concerns\ResolvesTimKerjaSession;
use App\Http\Controllers\Controller;
use App\Models\UsulanProgramKerja;
use Illuminate\Http\Request;

class DetailKegiatanController extends Controller
{
    use ResolvesTimKerjaSession;
    use GatesUsulanProgramKerja;

    // PUT /tim-kerja/usulan-program-kerja/{usulanProgramKerja}/detail
    public function storeOrUpdate(Request $request, UsulanProgramKerja $usulanProgramKerja)
    {
        $this->authorizeAksesUsulan($usulanProgramKerja);

        if ($usulanProgramKerja->isFieldLocked()) {
            return back()->with('feedback', ['type' => 'error', 'message' => 'Usulan ini sedang terkunci dan tidak dapat diubah.']);
        }

        $data = $request->validate([
            'nama_detail' => 'required|string|max:255',
            'tempat_pelaksanaan' => 'required|string|max:255',
            'bentuk_kegiatan' => 'required|in:Luring,Daring',
            'bulan_kegiatan' => 'required|array|min:1',
            'bulan_kegiatan.*' => 'integer|min:1|max:12',
            'anggaran' => 'required|numeric|min:0',
        ], [
            'nama_detail.required' => 'Nama Detail Kegiatan wajib diisi.',
            'tempat_pelaksanaan.required' => 'Tempat Pelaksanaan wajib diisi.',
            'bentuk_kegiatan.required' => 'Bentuk Kegiatan wajib diisi.',
            'bentuk_kegiatan.in' => 'Bentuk Kegiatan harus Luring atau Daring.',
            'bulan_kegiatan.required' => 'Bulan Kegiatan wajib dipilih minimal satu.',
            'anggaran.required' => 'Anggaran wajib diisi.',
            'anggaran.numeric' => 'Anggaran harus berupa angka.',
            'anggaran.min' => 'Anggaran tidak boleh negatif.',
        ]);

        $usulanProgramKerja->detailKegiatan()->updateOrCreate([], $data);

        return back()->with('feedback', ['type' => 'success', 'message' => 'Detail Kegiatan berhasil disimpan.']);
    }
}