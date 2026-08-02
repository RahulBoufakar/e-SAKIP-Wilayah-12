<?php

namespace Database\Seeders;

use App\Models\TahunAnggaran;
use App\Models\Triwulan;
use App\Models\TriwulanStatus;
use Illuminate\Database\Seeder;

class TahunAnggaranSeeder extends Seeder
{
    /**
     * ERD §4: seeder awal wajib berisi 1 Tahun Anggaran supaya sistem tidak
     * error/kosong saat pertama dijalankan. "Tahun ini" dihitung dinamis
     * (now()->year) supaya seeder tetap benar kapan pun dijalankan, bukan
     * hardcode 2026.
     *
     * Catatan: TahunAnggaran::booted() (created event) sudah auto-seed 4 baris
     * triwulan_status untuk tahun ini, tapi default status-nya 'non_aktif'
     * (lihat migration triwulan_status). Seeder ini menambahkan langkah
     * eksplisit mengaktifkan TW1 sesuai rekomendasi ERD §4, supaya selalu ada
     * satu Triwulan aktif secara default.
     */
    public function run(): void
    {
        $tahunIni = now()->year;

        $tahun = TahunAnggaran::firstOrCreate(['tahun' => $tahunIni]);

        $tw1 = Triwulan::where('kode', 'TW1')->first();

        if (! $tw1) {
            $this->command?->warn('Triwulan TW1 belum ada — jalankan TriwulanSeeder terlebih dahulu.');

            return;
        }

        // Idempoten: aman dipanggil ulang, activate() akan menonaktifkan TW lain
        // di tahun yang sama lalu mengaktifkan TW1 (Rule R-1, single-active).
        if (! TriwulanStatus::where('tahun_anggaran_id', $tahun->id)->where('status', 'aktif')->exists()) {
            TriwulanStatus::activate($tw1->id, $tahun->id);
        }

        $this->command?->info("Tahun Anggaran {$tahunIni} siap, {$tw1->kode} diaktifkan.");
    }
}
