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

    public function preview(UsulanProgramKerja $usulanProgramKerja, string $field)
    {
        abort_unless(in_array($field, ['kak', 'rab-pdf'], true), 404);

        $path = $this->resolveFilePath($usulanProgramKerja, $field);

        return response()->json([
            'mime' => 'application/pdf',
            // AUDIT § A1: dokumen kerja dipindah ke disk private — tidak lagi
            // dapat diakses langsung lewat URL publik, hanya lewat endpoint ini.
            'base64' => base64_encode(Storage::disk('private')->get($path)),
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