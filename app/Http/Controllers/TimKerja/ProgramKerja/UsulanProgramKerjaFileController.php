<?php

namespace App\Http\Controllers\TimKerja\ProgramKerja;

use App\Http\Controllers\Concerns\GatesUsulanProgramKerja;
use App\Http\Controllers\Concerns\ResolvesTimKerjaSession;
use App\Http\Controllers\Controller;
use App\Models\UsulanProgramKerja;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UsulanProgramKerjaFileController extends Controller
{
    use ResolvesTimKerjaSession;
    use GatesUsulanProgramKerja;

    private const FIELD_MAP = [
        'kak' => 'file_kak_pdf',
        'rab-pdf' => 'file_rab_pdf',
        'rab-excel' => 'file_rab_excel',
    ];

    /**
     * GET .../file/{field}/preview
     * AUDIT § A6/A7: di-stream langsung dari disk, bukan dibungkus JSON+base64
     * (base64 menambah ~33% ukuran payload & memuat seluruh file ke memori
     * PHP sekaligus). Tanpa Content-Disposition: attachment supaya browser
     * tetap merender di iframe (bukan memicu download seperti unduh()).
     */
    public function preview(UsulanProgramKerja $usulanProgramKerja, string $field): StreamedResponse
    {
        abort_unless(in_array($field, ['kak', 'rab-pdf'], true), 404); // RAB Excel tidak didukung iframe

        $path = $this->resolveFilePath($usulanProgramKerja, $field);

        return response()->stream(function () use ($path) {
            fpassthru(Storage::disk('private')->readStream($path));
        }, 200, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * GET .../file/{field}/unduh
     * Dipakai tombol/link "Unduh" — navigasi <a> biasa, memaksa browser
     * menyimpan file (Content-Disposition: attachment).
     */
    public function unduh(UsulanProgramKerja $usulanProgramKerja, string $field): StreamedResponse
    {
        $path = $this->resolveFilePath($usulanProgramKerja, $field);

        return Storage::disk('private')->download($path, basename($path));
    }

    private function resolveFilePath(UsulanProgramKerja $usulanProgramKerja, string $field): string
    {
        $this->authorize('view', $usulanProgramKerja);

        $column = self::FIELD_MAP[$field] ?? null;
        abort_unless($column, 404);

        $path = $usulanProgramKerja->$column;
        abort_unless($path && Storage::disk('private')->exists($path), 404);

        return $path;
    }
}
