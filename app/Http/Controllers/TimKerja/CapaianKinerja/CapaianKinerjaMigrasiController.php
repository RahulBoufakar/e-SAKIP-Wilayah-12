<?php

namespace App\Http\Controllers\TimKerja\CapaianKinerja;

use App\Events\ActivityOccurred;
use App\Http\Controllers\Concerns\ResolvesCapaianAktif;
use App\Http\Controllers\Concerns\ResolvesTimKerjaSession;
use App\Http\Controllers\Controller;
use App\Models\Iku;
use App\Services\CapaianKinerjaMigrasiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CapaianKinerjaMigrasiController extends Controller
{
    use ResolvesTimKerjaSession;
    use ResolvesCapaianAktif;

    public function __construct(private CapaianKinerjaMigrasiService $migrasi)
    {
    }

    // POST /tim-kerja/capaian-kinerja/{iku}/migrasi-triwulan
    public function __invoke(Request $request, Iku $iku)
    {
        $capaian = $this->resolveCapaianAktif($request, $iku, 'migrasi');

        $rencana = $this->migrasi->jalankan($capaian);
        if (! $rencana) {
            return back()->with('feedback', ['type' => 'error', 'message' => 'Tidak ada data triwulan sebelumnya untuk dimigrasi.']);
        }

        $baru = count($rencana['baru']);
        $diperbarui = count($rencana['perbarui']);
        $pesan = implode(', ', array_filter([
            $baru ? "{$baru} data baru disalin" : null,
            $diperbarui ? "{$diperbarui} data diperbarui" : null,
            $rencana['terkunci'] ? "{$rencana['terkunci']} data sudah divalidasi dan berbeda dari triwulan sebelumnya (tidak diubah)" : null,
            $rencana['bentrok'] ? "{$rencana['bentrok']} data tidak dapat diperbarui karena kuncinya bentrok dengan data lain" : null,
            $rencana['dilewati'] ? "{$rencana['dilewati']} data dilewati (sudah ada)" : null,
        ]));

        if ($baru + $diperbarui === 0) {
            return back()->with('feedback', [
                'type' => 'error',
                'message' => $pesan ? ucfirst($pesan).'.' : 'Tidak ada data tervalidasi di triwulan sebelumnya untuk dimigrasi.',
            ]);
        }

        event(new ActivityOccurred(
            subject: $capaian,
            description: "memigrasi Capaian Kinerja IKU {$iku->kode} dari triwulan sebelumnya ke {$capaian->triwulan->kode} ({$baru} baru, {$diperbarui} diperbarui)",
            causer: Auth::user(),
        ));

        return back()->with('feedback', [
            'type' => 'success',
            'message' => ucfirst($pesan).'. Data hasil migrasi berstatus draft; periksa bukti dukung lalu kirim untuk validasi.',
        ]);
    }
}
