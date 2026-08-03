<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ContextController extends Controller
{
    /**
     * ponytail-flag: tidak ada di API_Routes_Admin_eSAKIP_LLDikti11.md — ditambahkan
     * minimal supaya context bar Tahun Anggaran (Desain Sistem §2) punya tempat
     * menyimpan pilihan. Skip kalau ternyata tim frontend sudah punya mekanisme lain.
     */
    public function setTahunAnggaran(Request $request)
    {
        $data = $request->validate([
            'tahun_anggaran_id' => 'required|exists:tahun_anggaran,id',
        ], [
            'tahun_anggaran_id.required' => 'Tahun anggaran wajib dipilih.',
            'tahun_anggaran_id.exists' => 'Tahun anggaran tidak valid.',
        ]);

        $request->session()->put('tahun_anggaran_id', $data['tahun_anggaran_id']);

        return back();
    }
}
