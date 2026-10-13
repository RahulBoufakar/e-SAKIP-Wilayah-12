<?php

namespace App\Http\Controllers\TimKerja\CapaianKinerja;

use App\Http\Controllers\Concerns\ResolvesCapaianAktif;
use App\Http\Controllers\Concerns\ResolvesTimKerjaSession;
use App\Http\Controllers\Controller;
use App\Models\Iku;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CapaianKinerjaBuktiController extends Controller
{
    use ResolvesTimKerjaSession;
    use ResolvesCapaianAktif;

    private const FIELD_FILE = ['file_bukti_dukung', 'file_implementasi_ppks_antinarkoba_antikorupsi'];

    public function preview(Request $request, Iku $iku, string $komponen, int $barisId): StreamedResponse
    {
        return $this->stream($request, $iku, $komponen, $barisId, download: false);
    }

    public function unduh(Request $request, Iku $iku, string $komponen, int $barisId): StreamedResponse
    {
        return $this->stream($request, $iku, $komponen, $barisId, download: true);
    }

    private function stream(Request $request, Iku $iku, string $komponen, int $barisId, bool $download): StreamedResponse
    {
        $capaian = $this->resolveCapaianAktif($request, $iku);
        $baris = $capaian->relasi($komponen)->findOrFail($barisId);

        $field = $request->query('field', 'file_bukti_dukung');
        abort_unless(in_array($field, self::FIELD_FILE, true), 404); // whitelist, cegah baca kolom sembarang

        $path = $baris->{$field};
        abort_unless($path && Storage::disk('private')->exists($path), 404);

        if ($download) {
            return Storage::disk('private')->download($path);
        }

        return response()->stream(function () use ($path) {
            fpassthru(Storage::disk('private')->readStream($path));
        }, 200, ['Content-Type' => 'application/pdf']);
    }
}
