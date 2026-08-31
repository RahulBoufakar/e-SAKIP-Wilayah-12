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
     * Dibungkus JSON + base64 sengaja — supaya di level response HTTP, ini
     * tidak terlihat seperti "file yang bisa didownload" sama sekali bagi
     * download manager/ekstensi browser yang mengintip fetch(). Dipakai
     * iframe preview (hanya untuk field berformat PDF).
     */
    public function preview(UsulanProgramKerja $usulanProgramKerja, string $field)
    {
        abort_unless(in_array($field, ['kak', 'rab-pdf'], true), 404); // RAB Excel tidak didukung iframe

        $path = $this->resolveFilePath($usulanProgramKerja, $field);

        return response()->json([
            'mime' => 'application/pdf',
            'base64' => base64_encode(Storage::disk('public')->get($path)),
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

        return Storage::disk('public')->download($path, basename($path));
    }

    private function resolveFilePath(UsulanProgramKerja $usulanProgramKerja, string $field): string
    {
        $this->authorize('view', $usulanProgramKerja);

        $column = self::FIELD_MAP[$field] ?? null;
        abort_unless($column, 404);

        $path = $usulanProgramKerja->$column;
        abort_unless($path && Storage::disk('public')->exists($path), 404);

        return $path;
    }
}