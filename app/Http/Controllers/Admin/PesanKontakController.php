<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PesanKontak;
use App\Models\TimKerja;
use Illuminate\Http\Request;

class PesanKontakController extends Controller
{
    // GET /admin/pesan-kontak
    public function index(Request $request)
    {
        $pesanList = PesanKontak::with('timKerja')
            ->when($request->filled('tim_kerja_id'), fn ($q) => $q->where('tim_kerja_id', $request->tim_kerja_id))
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where(function ($sub) use ($request) {
                    $sub->where('nama', 'like', '%'.$request->search.'%')
                        ->orWhere('email', 'like', '%'.$request->search.'%');
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $timKerjaOptions = TimKerja::orderBy('nama_tim')->get(['id', 'nama_tim']);

        return view('admin.pesan-kontak.index', compact('pesanList', 'timKerjaOptions'));
    }
}