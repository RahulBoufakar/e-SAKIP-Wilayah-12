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

    public function preview(DokumenLaporanKegiatan $dokumenLaporanKegiatan)
    {
        $this->authorizeAkses($dokumenLaporanKegiatan);

        return response()->json([
            'mime' => 'application/pdf',
            'base64' => base64_encode(Storage::disk('public')->get($dokumenLaporanKegiatan->file_dokumen)),
        ]);
    }

    public function unduh(DokumenLaporanKegiatan $dokumenLaporanKegiatan): StreamedResponse
    {
        $this->authorizeAkses($dokumenLaporanKegiatan);

        return Storage::disk('public')->download(
            $dokumenLaporanKegiatan->file_dokumen,
            $dokumenLaporanKegiatan->nama_dokumen.'.pdf'
        );
    }

    private function authorizeAkses(DokumenLaporanKegiatan $dokumen): void
    {
        abort_unless($dokumen->file_dokumen && Storage::disk('public')->exists($dokumen->file_dokumen), 404);
        abort_unless(
            $this->activeTimKerjaIds()->contains($dokumen->laporan->proker->usulanProgramKerja->iku->tim_kerja_id),
            403
        );
    }
}