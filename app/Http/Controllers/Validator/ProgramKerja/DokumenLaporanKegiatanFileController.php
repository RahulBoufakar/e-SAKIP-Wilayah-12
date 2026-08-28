<?php

namespace App\Http\Controllers\Validator\ProgramKerja;

use App\Http\Controllers\Controller;
use App\Models\DokumenLaporanKegiatan;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DokumenLaporanKegiatanFileController extends Controller
{
    public function preview(DokumenLaporanKegiatan $dokumenLaporanKegiatan)
    {
        abort_unless($dokumenLaporanKegiatan->file_dokumen && Storage::disk('public')->exists($dokumenLaporanKegiatan->file_dokumen), 404);

        return response()->json([
            'mime' => 'application/pdf',
            'base64' => base64_encode(Storage::disk('public')->get($dokumenLaporanKegiatan->file_dokumen)),
        ]);
    }

    public function unduh(DokumenLaporanKegiatan $dokumenLaporanKegiatan): StreamedResponse
    {
        abort_unless($dokumenLaporanKegiatan->file_dokumen && Storage::disk('public')->exists($dokumenLaporanKegiatan->file_dokumen), 404);

        return Storage::disk('public')->download(
            $dokumenLaporanKegiatan->file_dokumen,
            $dokumenLaporanKegiatan->nama_dokumen.'.pdf'
        );
    }
}