<?php

namespace App\Http\Controllers;

class LandingController extends Controller
{
    // GET / (FR-L1/FR-L2). Belum ada auth — tombol langsung mengarah ke
    // tampilan per role (jawaban Open Question #1), tanpa proses login.
    public function index()
    {
        return view('landing.index');
    }
}
