<?php

namespace App\Http\Controllers\Validator\CapaianKinerja;

use App\Http\Controllers\Controller;
use App\Models\CapaianKinerjaDokumen;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CapaianKinerjaDokumenController extends Controller
{
    // GET /validator/capaian-kinerja/dokumen/{dokumen}/preview
    // AUDIT § A6/A7: stream langsung, bukan JSON+base64.
    public function preview(CapaianKinerjaDokumen $dokumen): StreamedResponse
    {
        $this->authorize('viewAsValidator', $dokumen); // AUDIT § B4

        return response()->stream(function () use ($dokumen) {
            fpassthru(Storage::disk('private')->readStream($dokumen->file_dokumen));
        }, 200, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    // GET /validator/capaian-kinerja/dokumen/{dokumen}/unduh
    public function unduh(CapaianKinerjaDokumen $dokumen): StreamedResponse
    {
        $this->authorize('viewAsValidator', $dokumen);

        return Storage::disk('private')->download($dokumen->file_dokumen, $dokumen->nama_dokumen.'.pdf');
    }
}
