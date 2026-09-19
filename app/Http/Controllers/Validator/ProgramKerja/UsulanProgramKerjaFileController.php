<?php

namespace App\Http\Controllers\Validator\ProgramKerja;

use App\Http\Controllers\Controller;
use App\Models\UsulanProgramKerja;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UsulanProgramKerjaFileController extends Controller
{
    private const FIELD_MAP = [
        'kak' => 'file_kak_pdf',
        'rab-pdf' => 'file_rab_pdf',
        'rab-excel' => 'file_rab_excel',
    ];

    // AUDIT § A6/A7: preview di-stream langsung (bukan dibungkus JSON+base64).
    // Base64 menambah ~33% ukuran payload dan mewajibkan seluruh file dimuat
    // penuh ke memori PHP sebelum dikirim — untuk PDF besar ini boros memori
    // & lebih lambat. StreamedResponse mengirim file per-chunk langsung dari
    // disk ke client tanpa Content-Disposition: attachment (supaya browser
    // tetap merender di iframe, bukan memaksa download seperti unduh()).
    public function preview(UsulanProgramKerja $usulanProgramKerja, string $field): StreamedResponse
    {
        abort_unless(in_array($field, ['kak', 'rab-pdf'], true), 404);

        $path = $this->resolveFilePath($usulanProgramKerja, $field);

        return response()->stream(function () use ($path) {
            fpassthru(Storage::disk('private')->readStream($path));
        }, 200, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function unduh(UsulanProgramKerja $usulanProgramKerja, string $field): StreamedResponse
    {
        $path = $this->resolveFilePath($usulanProgramKerja, $field);

        return Storage::disk('private')->download($path, basename($path));
    }

    private function resolveFilePath(UsulanProgramKerja $usulanProgramKerja, string $field): string
    {
        // AUDIT § B4: nyatakan aturan akses secara eksplisit lewat Policy,
        // bukan cuma mengandalkan middleware role:validator di route group.
        $this->authorize('viewAsValidator', $usulanProgramKerja);

        $column = self::FIELD_MAP[$field] ?? null;
        abort_unless($column, 404);

        $path = $usulanProgramKerja->$column;
        abort_unless($path && Storage::disk('private')->exists($path), 404);

        return $path;
    }
}
