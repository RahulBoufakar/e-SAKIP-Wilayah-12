<?php

namespace App\Http\Controllers\Validator\ProgramKerja;

use App\Http\Controllers\Controller;
use App\Models\DokumenLaporanKegiatan;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DokumenLaporanKegiatanFileController extends Controller
{
    public function preview(DokumenLaporanKegiatan $dokumenLaporanKegiatan): StreamedResponse
    {
        // AUDIT § B4
        $this->authorize('viewAsValidator', $dokumenLaporanKegiatan);

        abort_unless($dokumenLaporanKegiatan->file_dokumen && Storage::disk('private')->exists($dokumenLaporanKegiatan->file_dokumen), 404);

        // AUDIT § A6/A7: stream langsung, bukan JSON+base64.
        return response()->stream(function () use ($dokumenLaporanKegiatan) {
            fpassthru(Storage::disk('private')->readStream($dokumenLaporanKegiatan->file_dokumen));
        }, 200, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function unduh(DokumenLaporanKegiatan $dokumenLaporanKegiatan): StreamedResponse
    {
        $this->authorize('viewAsValidator', $dokumenLaporanKegiatan);

        abort_unless($dokumenLaporanKegiatan->file_dokumen && Storage::disk('private')->exists($dokumenLaporanKegiatan->file_dokumen), 404);

        return Storage::disk('private')->download(
            $dokumenLaporanKegiatan->file_dokumen,
            $dokumenLaporanKegiatan->nama_dokumen.'.pdf'
        );
    }
}
