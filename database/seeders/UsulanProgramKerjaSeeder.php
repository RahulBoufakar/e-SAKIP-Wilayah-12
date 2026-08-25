<?php

namespace Database\Seeders;

use App\Models\Iku;
use App\Models\TahunAnggaran;
use App\Models\UsulanProgramKerja;
use Illuminate\Database\Seeder;

class UsulanProgramKerjaSeeder extends Seeder
{
        /**
     * 1 Usulan Program Kerja contoh untuk tiap Tim Kerja yang di-assign lewat
     * SasaranKegiatanSeeder (Humas -> IKU 2.2, Kelembagaan -> IKU 1.1).
     * Key = kode IKU (format auto-generate "[iku {sasaran}.{urutan}]").
     */
    private const DATA = [
        '[iku 2.2]' => 'Sosialisasi Pencegahan Kekerasan dan Narkoba di Lingkungan PTS', // Humas
        '[iku 1.1]' => 'Penguatan Kelembagaan Layanan LLDIKTI Wilayah XII', // Kelembagaan
    ];

    public function run(): void
    {
        $tahunAnggaran = TahunAnggaran::orderByDesc('tahun')->first();

        if (! $tahunAnggaran) {
            $this->command?->warn('Tahun Anggaran belum ada — jalankan TahunAnggaranSeeder terlebih dahulu.');

            return;
        }

        foreach (self::DATA as $kodeIku => $namaUsulan) {
            $iku = Iku::where('kode', $kodeIku)->first();

            if (! $iku || ! $iku->tim_kerja_id) {
                $this->command?->warn("IKU dengan kode \"{$kodeIku}\" belum ada atau belum di-assign Tim Kerja — jalankan SasaranKegiatanSeeder terlebih dahulu.");

                continue;
            }

            UsulanProgramKerja::firstOrCreate([
                'iku_id' => $iku->id,
                'nama_usulan' => $namaUsulan,
            ], [
                'tahun' => $tahunAnggaran->tahun,
            ]);
        }

        $this->command?->info('Usulan Program Kerja contoh berhasil di-seed.');
    }
}