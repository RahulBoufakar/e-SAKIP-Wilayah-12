<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CapaianKinerja;
use App\Models\DokumenLaporanKegiatan;
use App\Models\UsulanProgramKerja;
use App\Services\DokumentasiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Preview/unduh read-only untuk halaman Dokumentasi Admin. Akses dijaga
 * middleware role:admin di routes/admin.php. File di-stream dari disk
 * 'private' (pola sama dengan controller file milik Validator).
 */
class DokumentasiFileController extends Controller
{
    private const USULAN_FIELD = [
        'kak' => 'file_kak_pdf',
        'rab-pdf' => 'file_rab_pdf',
        'rab-excel' => 'file_rab_excel',
    ];

    // --- Usulan Proker ---

    public function usulanPreview(UsulanProgramKerja $usulanProgramKerja, string $field): StreamedResponse
    {
        abort_unless(in_array($field, ['kak', 'rab-pdf'], true), 404); // Excel tidak punya pratinjau

        return $this->stream($this->pathUsulan($usulanProgramKerja, $field));
    }

    public function usulanUnduh(UsulanProgramKerja $usulanProgramKerja, string $field): StreamedResponse
    {
        $path = $this->pathUsulan($usulanProgramKerja, $field);

        return Storage::disk('private')->download($path, basename($path));
    }

    // --- Pelaporan Kegiatan ---

    public function laporanPreview(DokumenLaporanKegiatan $dokumenLaporanKegiatan): StreamedResponse
    {
        return $this->stream($this->pathLaporan($dokumenLaporanKegiatan));
    }

    public function laporanUnduh(DokumenLaporanKegiatan $dokumenLaporanKegiatan): StreamedResponse
    {
        return Storage::disk('private')->download(
            $this->pathLaporan($dokumenLaporanKegiatan),
            $dokumenLaporanKegiatan->nama_dokumen.'.pdf'
        );
    }

    // --- Capaian Kinerja ---

    public function capaianPreview(Request $request, DokumentasiService $service, string $tipe, string $komponen, int $barisId): StreamedResponse
    {
        return $this->stream($this->pathCapaian($request, $service, $tipe, $komponen, $barisId));
    }

    public function capaianUnduh(Request $request, DokumentasiService $service, string $tipe, string $komponen, int $barisId): StreamedResponse
    {
        return Storage::disk('private')->download($this->pathCapaian($request, $service, $tipe, $komponen, $barisId));
    }

    // --- helper ---

    private function pathUsulan(UsulanProgramKerja $usulan, string $field): string
    {
        $kolom = self::USULAN_FIELD[$field] ?? null;
        abort_unless($kolom, 404);

        return $this->pastikanAda($usulan->{$kolom});
    }

    private function pathLaporan(DokumenLaporanKegiatan $dokumen): string
    {
        return $this->pastikanAda($dokumen->file_dokumen);
    }

    private function pathCapaian(Request $request, DokumentasiService $service, string $tipe, string $komponen, int $barisId): string
    {
        $model = CapaianKinerja::komponenUntukTipe($tipe)[$komponen] ?? null;
        abort_unless($model, 404);

        // Whitelist kolom file: cegah pembacaan kolom sembarang.
        $field = $request->query('field', 'file_bukti_dukung');
        abort_unless(array_key_exists($field, $service->capaianFileFields($tipe, $komponen)), 404);

        $baris = $model::findOrFail($barisId);

        return $this->pastikanAda($baris->getAttributes()[$field] ?? null);
    }

    private function pastikanAda(?string $path): string
    {
        abort_unless($path && Storage::disk('private')->exists($path), 404);

        return $path;
    }

    private function stream(string $path): StreamedResponse
    {
        return response()->stream(function () use ($path) {
            fpassthru(Storage::disk('private')->readStream($path));
        }, 200, ['Content-Type' => 'application/pdf']);
    }
}