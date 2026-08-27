<?php

namespace App\Http\Controllers\Validator;

use App\Http\Controllers\Controller;
use App\Models\CapaianKinerjaDokumen;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CapaianKinerjaDokumenController extends Controller
{
    // GET /validator/capaian-kinerja/dokumen/{dokumen}/preview
    public function preview(CapaianKinerjaDokumen $dokumen)
    {
        return response()->json([
            'mime' => 'application/pdf',
            'base64' => base64_encode(Storage::disk('public')->get($dokumen->file_dokumen)),
        ]);
    }

    // GET /validator/capaian-kinerja/dokumen/{dokumen}/unduh
    public function unduh(CapaianKinerjaDokumen $dokumen): StreamedResponse
    {
        return Storage::disk('public')->download($dokumen->file_dokumen, $dokumen->nama_dokumen.'.pdf');
    }
}