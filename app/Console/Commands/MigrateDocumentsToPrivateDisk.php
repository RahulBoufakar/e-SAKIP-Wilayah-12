<?php

namespace App\Console\Commands;

use App\Models\CapaianKinerjaDokumen;
use App\Models\DokumenLaporanKegiatan;
use App\Models\UsulanProgramKerja;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * AUDIT-KEAMANAN-DAN-TECHNICAL-DEBT.md § A1.
 *
 * Satu kali jalan: memindahkan dokumen kerja (KAK/TOR, RAB, bukti capaian
 * kinerja, dokumen laporan kegiatan) dari disk 'public' ke disk 'private'.
 * Path relatif pada kolom DB TIDAK berubah — hanya disk penyimpanannya.
 *
 * Aman dijalankan berulang (idempoten): file yang sudah ada di disk
 * 'private' dilewati, file yang tidak ditemukan di 'public' dilewati
 * dengan peringatan.
 */
class MigrateDocumentsToPrivateDisk extends Command
{
    protected $signature = 'documents:migrate-to-private
                            {--dry-run : Tampilkan apa yang akan dipindahkan tanpa benar-benar memindahkan file}';

    protected $description = 'Migrasi satu-kali dokumen kerja dari disk public ke disk private (lihat AUDIT-KEAMANAN-DAN-TECHNICAL-DEBT.md § A1).';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $moved = 0;
        $moved += $this->moveUsulanFiles($dryRun);
        $moved += $this->moveCapaianKinerjaDokumen($dryRun);
        $moved += $this->moveDokumenLaporanKegiatan($dryRun);

        $this->info(($dryRun ? '[DRY RUN] ' : '')."Selesai. Total file dipindahkan: {$moved}.");

        return self::SUCCESS;
    }

    private function moveUsulanFiles(bool $dryRun): int
    {
        $count = 0;

        UsulanProgramKerja::where(function ($q) {
                $q->whereNotNull('file_kak_pdf')
                    ->orWhereNotNull('file_rab_pdf')
                    ->orWhereNotNull('file_rab_excel');
            })
            ->chunkById(50, function ($rows) use (&$count, $dryRun) {
                foreach ($rows as $row) {
                    foreach (['file_kak_pdf', 'file_rab_pdf', 'file_rab_excel'] as $field) {
                        if ($row->$field && $this->movePath($row->$field, $dryRun)) {
                            $count++;
                        }
                    }
                }
            });

        return $count;
    }

    private function moveCapaianKinerjaDokumen(bool $dryRun): int
    {
        $count = 0;

        CapaianKinerjaDokumen::whereNotNull('file_dokumen')
            ->chunkById(50, function ($rows) use (&$count, $dryRun) {
                foreach ($rows as $row) {
                    if ($this->movePath($row->file_dokumen, $dryRun)) {
                        $count++;
                    }
                }
            });

        return $count;
    }

    private function moveDokumenLaporanKegiatan(bool $dryRun): int
    {
        $count = 0;

        DokumenLaporanKegiatan::whereNotNull('file_dokumen')
            ->chunkById(50, function ($rows) use (&$count, $dryRun) {
                foreach ($rows as $row) {
                    if ($this->movePath($row->file_dokumen, $dryRun)) {
                        $count++;
                    }
                }
            });

        return $count;
    }

    /**
     * Pindahkan satu path relatif dari disk 'public' ke disk 'private'.
     * Path relatif TIDAK berubah — hanya disk penyimpanannya, sehingga
     * kolom di DB tidak perlu di-update sama sekali.
     */
    private function movePath(string $path, bool $dryRun): bool
    {
        if (! Storage::disk('public')->exists($path)) {
            $this->warn("Dilewati (tidak ditemukan di disk public): {$path}");

            return false;
        }

        if (Storage::disk('private')->exists($path)) {
            $this->line("Dilewati (sudah ada di disk private): {$path}");

            return false;
        }

        $this->line(($dryRun ? '[DRY RUN] ' : '')."Memindahkan: {$path}");

        if ($dryRun) {
            return true;
        }

        Storage::disk('private')->put($path, Storage::disk('public')->get($path));
        Storage::disk('public')->delete($path);

        return true;
    }
}
