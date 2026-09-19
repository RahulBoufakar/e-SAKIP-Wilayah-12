<?php

namespace App\Http\Controllers\TimKerja\ProgramKerja;

use App\Http\Controllers\Concerns\ResolvesTimKerjaSession;
use App\Http\Controllers\Controller;
use App\Models\DokumenLaporanKegiatan;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DokumenLaporanKegiatanFileController extends Controller
{
    use ResolvesTimKerjaSession;

    public function preview(DokumenLaporanKegiatan $dokumenLaporanKegiatan): StreamedResponse
    {
        $this->authorizeAkses($dokumenLaporanKegiatan);

        // AUDIT § A6/A7: stream langsung, bukan JSON+base64.
        return response()->stream(function () use ($dokumenLaporanKegiatan) {
            fpassthru(Storage::disk('private')->readStream($dokumenLaporanKegiatan->file_dokumen));
        }, 200, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function unduh(DokumenLaporanKegiatan $dokumenLaporanKegiatan): StreamedResponse
    {
        $this->authorizeAkses($dokumenLaporanKegiatan);

        return Storage::disk('private')->download(
            $dokumenLaporanKegiatan->file_dokumen,
            $dokumenLaporanKegiatan->nama_dokumen.'.pdf'
        );
    }

    private function authorizeAkses(DokumenLaporanKegiatan $dokumen): void
    {
        abort_unless($dokumen->file_dokumen && Storage::disk('private')->exists($dokumen->file_dokumen), 404);
        $this->authorize('view', $dokumen->laporan->proker);
    }
}
