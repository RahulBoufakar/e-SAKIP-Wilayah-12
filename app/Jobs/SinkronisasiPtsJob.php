<?php

namespace App\Jobs;

use App\Events\ActivityOccurred;
use App\Models\SinkronisasiPts;
use App\Services\Pddikti\PddiktiApiException;
use App\Services\PtsSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * $lanjutan = log sinkronisasi yang baru dijalankan SETELAH job ini berhasil
 * ("Sinkronkan semua": PTS dulu, baru akreditasi). Bila job ini gagal, log
 * lanjutan ditandai gagal supaya tidak menggantung 'diproses'.
 */
class SinkronisasiPtsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300; // harus < retry_after koneksi database (360)
    public int $tries = 1;     // percobaan ulang HTTP sudah di dalam driver

    public function __construct(public SinkronisasiPts $log, public ?SinkronisasiPts $lanjutan = null)
    {
        $this->onConnection(config('pddikti.queue.koneksi'));
        $this->onQueue(config('pddikti.queue.antrean'));
    }

    public function handle(PtsSyncService $service): void
    {
        $this->log->update(['mulai_at' => now()]);

        $ringkasan = $this->log->jenis === 'pts'
            ? $service->sinkronkanPts()
            : $service->sinkronkanAkreditasi();

        $this->log->update(['status' => 'berhasil', 'ringkasan' => $ringkasan, 'selesai_at' => now()]);

        // Sebelum event: bila listener event melempar galat, lanjutan sudah terantrekan.
        // Dilewati bila Admin sudah menandainya gagal (macet) selagi job ini berjalan.
        if ($this->lanjutan?->fresh()?->status === 'diproses') {
            Bus::dispatch(new self($this->lanjutan));
        }

        // Satu event di akhir (bukan per baris): listener eviction cache jalan di setiap event.
        event(new ActivityOccurred(
            subject: $this->log,
            description: 'menyelesaikan sinkronisasi data '.$this->namaJenis().' dari '.$this->namaSumber(),
            causer: $this->log->pemicu,
            properties: $ringkasan,
        ));
    }

    public function failed(Throwable $e): void
    {
        // Jangan menimpa log yang sudah berhasil (mis. listener event yang melempar galat setelahnya).
        if ($this->log->fresh()?->status === 'berhasil') {
            return;
        }

        // Pesan PddiktiApiException sudah aman ditampilkan (tanpa API key). Galat lain hanya ke log aplikasi.
        $aman = $e instanceof PddiktiApiException;
        if (! $aman) {
            Log::error('Sinkronisasi PTS gagal ('.get_class($e).'): '.$e->getMessage());
        }
        $catatan = $aman ? $e->getMessage() : 'Terjadi galat internal saat sinkronisasi. Periksa log aplikasi.';

        $this->log->update(['status' => 'gagal', 'catatan' => $catatan, 'selesai_at' => now()]);

        $this->lanjutan?->update([
            'status' => 'gagal',
            'catatan' => 'Dibatalkan karena sinkronisasi PTS gagal.',
            'selesai_at' => now(),
        ]);

        event(new ActivityOccurred(
            subject: $this->log,
            description: 'sinkronisasi data '.$this->namaJenis().' dari '.$this->namaSumber().' gagal',
            causer: $this->log->pemicu,
            properties: ['catatan' => $catatan],
        ));
    }

    private function namaJenis(): string
    {
        return $this->log->jenis === 'pts' ? 'PTS' : 'akreditasi PTS';
    }

    private function namaSumber(): string
    {
        return config('pddikti.driver') === 'lldikti12' ? 'LLDikti 12' : 'PDDikti';
    }
}