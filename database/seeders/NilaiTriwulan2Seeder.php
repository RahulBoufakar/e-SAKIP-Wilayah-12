<?php

namespace Database\Seeders;

use App\Models\CapaianKinerja;
use App\Models\Iku;
use App\Models\SasaranKegiatan;
use App\Models\TahunAnggaran;
use App\Models\Triwulan;
use Illuminate\Database\Seeder;

class NilaiTriwulan2Seeder extends Seeder
{
    /**
     * Butuh SasaranKegiatanSeeder sudah jalan duluan (IKU dicari lewat deskripsi).
     * Setiap IKU: [deskripsi, target TW2, realisasi TW2].
     */
    private const DATA = [
        'Meningkatnya kualitas layanan Lembaga Layanan Pendidikan Tinggi (LLDIKTI)' => [
            ['Keunggulan layanan LLDIKTI', 50, 90],
            ['Arsitektur Perguruan Tinggi Swasta (PTS)', 50, 100],
            ['Tata kelola LLDIKTI yang berkualitas dan berintegritas', 40, 0],
        ],
        'Meningkatnya efektivitas sosialisasi kebijakan pendidikan tinggi' => [
            ['Fasilitasi peningkatan mutu pendidikan pada perguruan tinggi swasta oleh LLDIKTI', 48.56, 95.65],
            ['Pencegahan dan penanganan kekerasan, narkoba, dan korupsi', 48.56, 50],
        ],
        'Meningkatnya inovasi perguruan tinggi dalam rangka meningkatkan mutu pendidikan' => [
            ['Fasilitasi pengembangan kemahasiswaan dan prestasi oleh LLDIKTI', 48.56, 69.56],
            ['Jumlah dosen PTS yang meningkat jabatan fungsionalnya', 38, 34],
            ['Fasilitasi Peningkatan Kinerja Penelitian, Publikasi, Pengabdian pada Masyarakat dan Kemitraan PTS', 1.08, 0.55],
        ],
        'Meningkatnya tata kelola Lembaga Layanan Pendidikan Tinggi (LLDIKTI)' => [
            ['Nilai Kinerja Anggaran atas Pelaksanaan RKA-K/L', 49, 54.09],
        ],
    ];

    public function run(): void
    {
        $tahunAnggaran = TahunAnggaran::orderByDesc('tahun')->first();

        if (! $tahunAnggaran) {
            $this->command?->warn('Tahun Anggaran belum ada — jalankan TahunAnggaranSeeder terlebih dahulu.');

            return;
        }

        $tw2 = Triwulan::where('kode', 'TW2')->first();

        if (! $tw2) {
            $this->command?->warn('Triwulan TW2 belum ada — jalankan TriwulanSeeder terlebih dahulu.');

            return;
        }

        foreach (self::DATA as $namaSasaran => $ikuList) {
            $sasaran = SasaranKegiatan::where('tahun_anggaran_id', $tahunAnggaran->id)
                ->where('nama_sasaran', $namaSasaran)
                ->first();

            if (! $sasaran) {
                $this->command?->warn("Sasaran Kegiatan \"{$namaSasaran}\" belum ada — jalankan SasaranKegiatanSeeder terlebih dahulu.");

                continue;
            }

            foreach ($ikuList as [$deskripsi, $target, $realisasi]) {
                $iku = Iku::where('sasaran_kegiatan_id', $sasaran->id)
                    ->where('deskripsi', $deskripsi)
                    ->first();

                if (! $iku) {
                    $this->command?->warn("IKU \"{$deskripsi}\" belum ada — jalankan SasaranKegiatanSeeder terlebih dahulu.");

                    continue;
                }

                CapaianKinerja::updateOrCreate(
                    ['iku_id' => $iku->id, 'triwulan_id' => $tw2->id, 'tahun_anggaran_id' => $tahunAnggaran->id],
                    ['target' => $target, 'realisasi' => $realisasi]
                );
            }
        }

        $this->command?->info('Nilai Target & Realisasi Triwulan 2 berhasil di-seed.');
    }
}