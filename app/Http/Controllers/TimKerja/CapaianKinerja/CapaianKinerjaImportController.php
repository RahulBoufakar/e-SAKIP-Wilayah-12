<?php

namespace App\Http\Controllers\TimKerja\CapaianKinerja;

use App\Events\ActivityOccurred;
use App\Http\Controllers\Concerns\ResolvesCapaianAktif;
use App\Http\Controllers\Concerns\ResolvesTimKerjaSession;
use App\Http\Controllers\Controller;
use App\Models\Iku;
use App\Services\AkreditasiImportService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CapaianKinerjaImportController extends Controller
{
    use ResolvesTimKerjaSession;
    use ResolvesCapaianAktif;

    public function __construct(private AkreditasiImportService $import)
    {
    }

    // POST /tim-kerja/capaian-kinerja/{iku}/import-akreditasi
    public function __invoke(Request $request, Iku $iku)
    {
        $capaian = $this->resolveCapaianAktif($request, $iku, 'importPddikti');

        try {
            $h = $this->import->jalankan($capaian);
        } catch (QueryException $e) {
            if ((int) $e->getCode() !== 23000) {
                throw $e;
            }

            // Jaring pengaman: unique constraint (capaian_kinerja_id + pts_id), mis. dua request bersamaan.
            return back()->with('feedback', ['type' => 'error', 'message' => 'Import bentrok dengan data lain yang baru tersimpan. Muat ulang halaman lalu coba lagi.']);
        }

        if ($h['total_sumber'] === 0) {
            return back()->with('feedback', ['type' => 'error', 'message' => 'Belum ada data akreditasi hasil sinkronisasi. Hubungi Administrator untuk menjalankan sinkronisasi akreditasi.']);
        }

        $manual = $h['manual_dilewati'];
        $sisa = count($manual) - 5;
        $bagian = array_filter([
            $h['baru'] ? "{$h['baru']} data baru ditambahkan" : null,
            $h['diperbarui'] ? "{$h['diperbarui']} data diperbarui" : null,
            $h['tidak_berubah'] ? "{$h['tidak_berubah']} data sudah sesuai" : null,
            $h['terkunci'] ? "{$h['terkunci']} data sedang/sudah divalidasi (tidak diubah)" : null,
            $h['kedaluwarsa'] ? "{$h['kedaluwarsa']} PTS dilewati karena masa berlaku akreditasi sudah lewat" : null,
            $h['tidak_lengkap'] ? "{$h['tidak_lengkap']} PTS dilewati karena data SK dari API tidak lengkap" : null,
            $manual ? count($manual).' PTS dilewati karena sudah ada isian manual ('.implode(', ', array_slice($manual, 0, 5)).($sisa > 0 ? " dan {$sisa} lainnya" : '').')' : null,
        ]);
        $berubah = $h['baru'] + $h['diperbarui'] > 0;
        $pesan = ucfirst(implode(', ', $bagian)).'.'.($berubah ? ' Data berstatus draft; periksa lalu kirim untuk validasi.' : '');

        $sumber = config('pddikti.driver') === 'lldikti12' ? 'LLDikti 12' : 'PDDikti';
        event(new ActivityOccurred(
            subject: $capaian,
            description: "mengimport data akreditasi dari {$sumber} pada IKU {$iku->kode} — {$capaian->triwulan->kode} ({$h['baru']} baru, {$h['diperbarui']} diperbarui)",
            causer: Auth::user(),
            properties: $h,
        ));

        return back()->with('feedback', ['type' => $berubah ? 'success' : 'warning', 'message' => $pesan]);
    }
}