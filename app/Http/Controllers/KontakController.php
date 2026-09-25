<?php

namespace App\Http\Controllers;

use App\Models\PesanKontak;
use App\Models\TimKerja;
use Illuminate\Http\Request;

class KontakController extends Controller
{
    // GET /kontak
    public function create()
    {
        $timKerjaList = TimKerja::orderBy('nama_tim')->get();

        return view('kontak.create', compact('timKerjaList'));
    }

    // POST /kontak
    public function store(Request $request)
    {
        $data = $request->validate([
            'nama' => 'required|string|max:150',
            'email' => 'required|email|max:150',
            'tim_kerja_id' => 'nullable|exists:tim_kerja,id',
            'gambar' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
            'pesan' => 'required|string',
        ], [
            'nama.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'tim_kerja_id.exists' => 'Tim Kerja tidak valid.',
            'gambar.image' => 'File harus berupa gambar.',
            'gambar.mimes' => 'Gambar harus berformat PNG, JPG, atau WEBP.',
            'gambar.max' => 'Ukuran gambar maksimal 2 MB.',
            'pesan.required' => 'Pesan wajib diisi.',
        ]);

        if ($request->hasFile('gambar')) {
            $data['gambar'] = $request->file('gambar')->store('pesan-kontak', 'public');
        }

        PesanKontak::create($data);

        return back()->with('status', 'Pesan Anda berhasil dikirim. Terima kasih telah menghubungi kami.');
    }
}