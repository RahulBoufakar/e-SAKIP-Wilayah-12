<?php

namespace App\Http\Controllers\Admin\Tools;

use App\Http\Controllers\Controller;

class SinkronisasiController extends Controller
{
    // GET /admin/tools/sinkronisasi (FR-34/FR-35: placeholder, tidak ada aksi backend)
    public function index()
    {
        return view('admin.tools.sinkronisasi.index');
    }
}
