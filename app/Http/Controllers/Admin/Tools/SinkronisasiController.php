<?php

namespace App\Http\Controllers\Admin\Tools;

use App\Events\ActivityOccurred;
use App\Http\Controllers\Controller;
use App\Jobs\SinkronisasiPtsJob;
use App\Models\Pts;
use App\Models\SinkronisasiPts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class SinkronisasiController extends Controller
{
    // Log 'diproses' lebih tua dari ini dianggap macet (worker mati) — TDD §6.
    private const MACET_MENIT = 15;

    // GET /admin/tools/sinkronisasi
    public function index()
    {
        $this->authorize('sync', Pts::class);

        $diproses = SinkronisasiPts::diproses()->get();

        return view('admin.tools.sinkronisasi.index', [
            'namaSumber' => $this->namaSumber(),
            'masalahKonfigurasi' => $this->masalahKonfigurasi(),
            'riwayat' => SinkronisasiPts::with('pemicu')->latest('id')->paginate(10),
            'idsDiproses' => $diproses->pluck('id')->all(),
            'macet' => $diproses->filter(fn ($l) => $this->macet($l))->values(),
            'menitMacet' => self::MACET_MENIT,
            'terakhirBerhasil' => SinkronisasiPts::where('status', 'berhasil')
                ->selectRaw('jenis, MAX(selesai_at) as terakhir')
                ->groupBy('jenis')
                ->pluck('terakhir', 'jenis'),
        ]);
    }

    // POST /admin/tools/sinkronisasi/pts
    public function pts(): RedirectResponse
    {
        return $this->antrekan(['pts']);
    }

    // POST /admin/tools/sinkronisasi/akreditasi
    public function akreditasi(): RedirectResponse
    {
        return $this->antrekan(['akreditasi']);
    }

    // POST /admin/tools/sinkronisasi/semua — PTS dulu, akreditasi dijalankan job PTS setelah berhasil
    public function semua(): RedirectResponse
    {
        return $this->antrekan(['pts', 'akreditasi']);
    }

    // GET /admin/tools/sinkronisasi/status?ids[]=1&ids[]=2 — polling halaman
    public function status(Request $request)
    {
        $this->authorize('sync', Pts::class);

        $ids = array_map('intval', (array) $request->query('ids', []));

        return response()->json(SinkronisasiPts::whereIn('id', $ids)->pluck('status', 'id'));
    }

    // PUT /admin/tools/sinkronisasi/{sinkronisasiPts}/tandai-gagal — hanya untuk log macet
    public function tandaiGagal(SinkronisasiPts $sinkronisasiPts): RedirectResponse
    {
        $this->authorize('sync', Pts::class);

        if ($sinkronisasiPts->status !== 'diproses') {
            return $this->galat('Sinkronisasi ini sudah selesai.');
        }

        // Dijaga di server: menandai gagal job yang masih berjalan akan membuka jalan bagi job kedua.
        if (! $this->macet($sinkronisasiPts)) {
            return $this->galat('Sinkronisasi baru dianggap macet setelah '.self::MACET_MENIT.' menit.');
        }

        $sinkronisasiPts->update([
            'status' => 'gagal',
            'catatan' => 'Ditandai gagal oleh Admin: tidak selesai dalam '.self::MACET_MENIT.' menit (worker antrean kemungkinan tidak berjalan).',
            'selesai_at' => now(),
        ]);

        event(new ActivityOccurred(
            subject: $sinkronisasiPts,
            description: "menandai sinkronisasi #{$sinkronisasiPts->id} ({$sinkronisasiPts->jenis}) sebagai gagal karena macet",
            causer: Auth::user(),
        ));

        return back()->with('feedback', ['type' => 'success', 'message' => 'Sinkronisasi ditandai gagal. Sinkronisasi baru dapat dijalankan.']);
    }

    /** @param  array<int, string>  $jenis  urutan eksekusi; elemen berikutnya jadi lanjutan job pertama */
    private function antrekan(array $jenis): RedirectResponse
    {
        $this->authorize('sync', Pts::class);

        if ($pesan = $this->masalahKonfigurasi()) {
            return $this->galat($pesan);
        }

        // Kunci hanya membungkus cek + pembuatan log, bukan seluruh proses.
        $logs = Cache::lock('sinkronisasi-pts', 10)->get(function () use ($jenis) {
            if (SinkronisasiPts::diproses()->exists()) {
                return null;
            }

            return array_map(fn ($j) => SinkronisasiPts::create(['jenis' => $j, 'dipicu_oleh' => Auth::id()]), $jenis);
        });

        if ($logs === false) {
            return $this->galat('Permintaan sinkronisasi lain sedang diproses. Coba lagi sebentar lagi.');
        }
        if ($logs === null) {
            return $this->galat('Masih ada sinkronisasi yang berjalan. Tunggu sampai selesai.');
        }

        $apa = count($jenis) === 2 ? 'data PTS dan akreditasi' : ($jenis[0] === 'pts' ? 'data PTS' : 'akreditasi PTS');
        event(new ActivityOccurred(
            subject: $logs[0],
            description: "memulai sinkronisasi {$apa} dari {$this->namaSumber()}",
            causer: Auth::user(),
        ));

        try {
            Bus::dispatch(new SinkronisasiPtsJob($logs[0], $logs[1] ?? null));
        } catch (Throwable $e) {
            Log::error('Gagal mengantrekan sinkronisasi PTS ('.get_class($e).'): '.$e->getMessage());

            // Hanya yang masih 'diproses': dengan koneksi sync, job yang gagal sudah menandai lognya sendiri.
            SinkronisasiPts::whereIn('id', collect($logs)->pluck('id'))->diproses()->update([
                'status' => 'gagal',
                'catatan' => 'Gagal mengantrekan job. Periksa konfigurasi antrean dan log aplikasi.',
                'selesai_at' => now(),
            ]);

            return $this->galat('Sinkronisasi tidak dapat dijalankan. Periksa riwayat dan log aplikasi.');
        }

        return back()->with('feedback', ['type' => 'success', 'message' => 'Sinkronisasi diantrekan. Proses biasanya mulai dalam kurang dari satu menit.']);
    }

    /** Pesan konfigurasi yang aman ditampilkan (tidak memuat API key), atau null bila lengkap. */
    private function masalahKonfigurasi(): ?string
    {
        $driver = (string) config('pddikti.driver');
        $cfg = config("pddikti.drivers.{$driver}");

        if (! $cfg) {
            return "PDDIKTI_DRIVER \"{$driver}\" tidak dikenal. Gunakan lldikti12 atau pddikti.";
        }

        if (blank($cfg['base_url'] ?? null) || blank($cfg['api_key'] ?? null)) {
            $awalan = strtoupper($driver);

            return "Konfigurasi API belum lengkap. Isi {$awalan}_BASE_URL dan {$awalan}_API_KEY di .env, lalu jalankan php artisan config:clear.";
        }

        return null;
    }

    private function macet(SinkronisasiPts $log): bool
    {
        return $log->status === 'diproses' && $log->created_at->lt(now()->subMinutes(self::MACET_MENIT));
    }

    private function namaSumber(): string
    {
        return config('pddikti.driver') === 'lldikti12' ? 'LLDikti 12' : 'PDDikti';
    }

    private function galat(string $pesan): RedirectResponse
    {
        return back()->with('feedback', ['type' => 'error', 'message' => $pesan]);
    }
}