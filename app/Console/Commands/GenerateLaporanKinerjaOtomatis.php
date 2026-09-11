<?php

namespace App\Console\Commands;

use App\Models\TahunAnggaran;
use App\Models\Triwulan;
use App\Services\LaporanKinerjaService;
use Illuminate\Console\Command;
use Throwable;

class GenerateLaporanKinerjaOtomatis extends Command
{
    protected $signature = 'laporan-kinerja:generate-otomatis';

    protected $description = 'Generate Laporan Kinerja otomatis berdasarkan kalender tetap (PRD §4.2). Dijadwalkan harian, hanya bertindak pada tanggal 1.';

    // urutan Triwulan yang baru saja berakhir, per bulan trigger (§4.2)
    private const PEMICU_TRIWULANAN = [4 => 1, 7 => 2, 10 => 3];

    public function handle(LaporanKinerjaService $service): int
    {
        if (now()->day !== 1) {
            return self::SUCCESS;
        }

        $this->generateBulananLalu($service);

        $bulanIni = now()->month;

        if (isset(self::PEMICU_TRIWULANAN[$bulanIni])) {
            $this->generateTriwulanBerjalan($service, self::PEMICU_TRIWULANAN[$bulanIni]);
        }

        if ($bulanIni === 1) {
            $this->generateTw4DanTahunanSebelumnya($service);
        }

        return self::SUCCESS;
    }

    private function generateBulananLalu(LaporanKinerjaService $service): void
    {
        $bulanLalu = now()->subMonthNoOverflow();
        $tahunAnggaran = TahunAnggaran::where('tahun', $bulanLalu->year)->first();

        if (! $tahunAnggaran) {
            $this->warn("Tahun Anggaran {$bulanLalu->year} tidak ditemukan — laporan bulanan dilewati.");

            return;
        }

        $this->tryGenerate(fn () => $service->generateBulanan($tahunAnggaran->id, $bulanLalu->month), 'bulanan');
    }

    private function generateTriwulanBerjalan(LaporanKinerjaService $service, int $urutan): void
    {
        $tahunAnggaran = TahunAnggaran::where('tahun', now()->year)->first();
        $triwulan = Triwulan::where('urutan', $urutan)->first();

        if (! $tahunAnggaran || ! $triwulan) {
            $this->warn('Data Tahun Anggaran/Triwulan tidak lengkap — laporan triwulanan dilewati.');

            return;
        }

        $this->tryGenerate(fn () => $service->generateTriwulanan($tahunAnggaran->id, $triwulan->id), "triwulanan TW{$urutan}");
    }

    private function generateTw4DanTahunanSebelumnya(LaporanKinerjaService $service): void
    {
        $tahunSebelumnya = TahunAnggaran::where('tahun', now()->year - 1)->first();
        $tw4 = Triwulan::where('kode', 'TW4')->first();

        if (! $tahunSebelumnya || ! $tw4) {
            $this->warn('Tahun Anggaran sebelumnya/TW4 tidak ditemukan — laporan TW4 & tahunan dilewati.');

            return;
        }

        $this->tryGenerate(fn () => $service->generateTriwulanan($tahunSebelumnya->id, $tw4->id), 'triwulanan TW4');
        $this->tryGenerate(fn () => $service->generateTahunan($tahunSebelumnya->id), 'tahunan');
    }

    // §6.3 poin 5: setiap pemanggilan dibungkus try-catch independen per jenis.
    private function tryGenerate(callable $callback, string $label): void
    {
        try {
            $callback();
            $this->info("Laporan {$label} berhasil dipicu.");
        } catch (Throwable $e) {
            $this->error("Gagal memicu laporan {$label}: {$e->getMessage()}");
        }
    }
}
