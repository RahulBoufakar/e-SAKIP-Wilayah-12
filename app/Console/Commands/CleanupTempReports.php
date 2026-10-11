<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * RAB Generator 02-arsitektur.md / 08 langkah 8: menghapus berkas hasil preview
 * (disk 'private', folder temp/, dibuat RabReportController::preview) yang sudah
 * lewat batas. Token di cache kedaluwarsa dalam 20 menit; berkasnya baru dihapus
 * di sini setelah 60 menit, jadi unduhan yang sedang berjalan tidak terputus.
 *
 * Hanya menyentuh temp/*.xlsx. Berkas lain di folder itu dan folder lain dibiarkan.
 */
class CleanupTempReports extends Command
{
    protected $signature = 'rab-generator:cleanup-temp
                            {--menit=60 : Hapus berkas temp yang lebih lama dari N menit}';

    protected $description = 'Hapus berkas hasil preview RAB Generator di storage/app/private/temp yang sudah lebih lama dari batas (default 60 menit).';

    public function handle(): int
    {
        $batas = now()->subMinutes((int) $this->option('menit'))->getTimestamp();
        $disk = Storage::disk('private');
        $dihapus = 0;

        foreach ($disk->files('temp') as $path) {
            if (! str_ends_with($path, '.xlsx')) {
                continue;
            }

            if ($disk->lastModified($path) < $batas) {
                $disk->delete($path);
                $dihapus++;
            }
        }

        $this->info("Selesai. Berkas temp dihapus: {$dihapus}.");

        return self::SUCCESS;
    }
}
