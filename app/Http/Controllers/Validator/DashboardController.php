<?php

namespace App\Http\Controllers\Validator;

use App\Http\Controllers\Controller;
use App\Models\UsulanProgramKerja;

class DashboardController extends Controller
{
    // GET /validator/dashboard
    public function index()
    {
        $jumlahMenunggu = UsulanProgramKerja::where('status_validasi', 'menunggu_validasi')->count();
        $jumlahDisetujui = UsulanProgramKerja::where('status_validasi', 'approved')->count();
        $jumlahDitolak = UsulanProgramKerja::where('status_validasi', 'rejected')->count();

        return view('validator.dashboard.index', compact('jumlahMenunggu', 'jumlahDisetujui', 'jumlahDitolak'));
    }
}