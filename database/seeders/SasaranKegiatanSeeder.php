<?php

namespace Database\Seeders;

use App\Models\Iku;
use App\Models\SasaranKegiatan;
use App\Models\TahunAnggaran;
use Illuminate\Database\Seeder;

class SasaranKegiatanSeeder extends Seeder
{
    /**
     * Setiap IKU: [deskripsi, target_pk, satuan].
     * Nilai target/realisasi per triwulan sengaja TIDAK di sini — lihat NilaiTriwulan1Seeder.
     */
    private const DATA = [
        [
            'nama_sasaran' => 'Meningkatnya kualitas layanan Lembaga Layanan Pendidikan Tinggi (LLDIKTI)',
            'iku' => [
                ['Keunggulan layanan LLDIKTI', 100, '%'],
                ['Arsitektur Perguruan Tinggi Swasta (PTS)', 100, '%'],
                ['Tata kelola LLDIKTI yang berkualitas dan berintegritas', 80, 'Nilai'],
            ],
        ],
        [
            'nama_sasaran' => 'Meningkatnya efektivitas sosialisasi kebijakan pendidikan tinggi',
            'iku' => [
                ['Fasilitasi peningkatan mutu pendidikan pada perguruan tinggi swasta oleh LLDIKTI', 97.72, '%'],
                ['Pencegahan dan penanganan kekerasan, narkoba, dan korupsi', 97.72, '%'],
            ],
        ],
        [
            'nama_sasaran' => 'Meningkatnya inovasi perguruan tinggi dalam rangka meningkatkan mutu pendidikan',
            'iku' => [
                ['Fasilitasi pengembangan kemahasiswaan dan prestasi oleh LLDIKTI', 97.72, '%'],
                ['Jumlah dosen PTS yang meningkat jabatan fungsionalnya', 135, 'Orang'],
                ['Fasilitasi Peningkatan Kinerja Penelitian, Publikasi, Pengabdian pada Masyarakat dan Kemitraan PTS', 2.15, '%'],
            ],
        ],
        [
            'nama_sasaran' => 'Meningkatnya tata kelola Lembaga Layanan Pendidikan Tinggi (LLDIKTI)',
            'iku' => [
                ['Nilai Kinerja Anggaran atas Pelaksanaan RKA-K/L', 98, 'Nilai'],
            ],
        ],
    ];

    public function run(): void
    {
        $tahunAnggaran = TahunAnggaran::orderByDesc('tahun')->first();

        if (! $tahunAnggaran) {
            $this->command?->warn('Tahun Anggaran belum ada — jalankan TahunAnggaranSeeder terlebih dahulu.');

            return;
        }

        foreach (self::DATA as $s) {
            $sasaran = SasaranKegiatan::firstOrCreate([
                'tahun_anggaran_id' => $tahunAnggaran->id,
                'nama_sasaran' => $s['nama_sasaran'],
            ]);

            foreach ($s['iku'] as [$deskripsi, $targetPk, $satuan]) {
                Iku::firstOrCreate([
                    'sasaran_kegiatan_id' => $sasaran->id,
                    'deskripsi' => $deskripsi,
                ], [
                    'jenis' => 'IKU',
                    'target_pk' => $targetPk,
                    'satuan' => $satuan,
                ]);
            }
        }

        $this->command?->info('Data Sasaran Kegiatan dan IKU berhasil di-seed.');
    }
}