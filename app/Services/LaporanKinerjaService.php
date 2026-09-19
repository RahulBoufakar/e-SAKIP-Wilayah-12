<?php

namespace App\Services;

use App\Jobs\GenerateLaporanKinerjaJob;
use App\Models\LaporanKinerja;
use Illuminate\Support\Facades\Cache;

/**
 * Titik tunggal logika pembuatan Laporan Kinerja (PRD §6.1), dipakai oleh:
 * (a) command terjadwal, (b) tombol generate manual Pimpinan, (c) tombol
 * generate manual Admin. Setiap method hanya membuat record berstatus
 * 'diproses' lalu men-dispatch job async — rendering PDF terjadi di job.
 *
 * AUDIT § A8: dibungkus Cache::lock() per kombinasi jenis+periode supaya dua
 * klik "Generate" yang nyaris bersamaan pada periode yang sama tidak
 * menghasilkan 2 baris `diproses` dengan versi yang saling tumpang tindih.
 * block(5) berarti request kedua menunggu maksimal 5 detik; kalau request
 * pertama belum selesai (nextVersi() + create() sangat cepat, jadi dalam
 * praktiknya nyaris tidak pernah menunggu selama itu), request kedua akan
 * melempar LockTimeoutException alih-alih diam-diam membuat versi duplikat.
 */
class LaporanKinerjaService
{
    public function generateBulanan(int $tahunAnggaranId, int $bulan, ?int $generatedBy = null): LaporanKinerja
    {
        return $this->buatDanProses([
            'jenis' => 'bulanan',
            'tahun_anggaran_id' => $tahunAnggaranId,
            'bulan' => $bulan,
            'triwulan_id' => null,
            'generated_by' => $generatedBy,
        ]);
    }

    public function generateTriwulanan(int $tahunAnggaranId, int $triwulanId, ?int $generatedBy = null): LaporanKinerja
    {
        return $this->buatDanProses([
            'jenis' => 'triwulanan',
            'tahun_anggaran_id' => $tahunAnggaranId,
            'bulan' => null,
            'triwulan_id' => $triwulanId,
            'generated_by' => $generatedBy,
        ]);
    }

    public function generateTahunan(int $tahunAnggaranId, ?int $generatedBy = null): LaporanKinerja
    {
        return $this->buatDanProses([
            'jenis' => 'tahunan',
            'tahun_anggaran_id' => $tahunAnggaranId,
            'bulan' => null,
            'triwulan_id' => null,
            'generated_by' => $generatedBy,
        ]);
    }

    private function buatDanProses(array $data): LaporanKinerja
    {
        // AUDIT § A8: kunci per kombinasi jenis+periode (bukan lock global),
        // supaya generate laporan bulanan Januari tetap bisa berjalan
        // bersamaan dengan generate laporan tahunan tanpa saling menunggu.
        $lockKey = "generate-laporan:{$data['jenis']}:{$data['tahun_anggaran_id']}:{$data['bulan']}:{$data['triwulan_id']}";

        return Cache::lock($lockKey, 10)->block(5, function () use ($data) {
            $data['versi'] = LaporanKinerja::nextVersi($data['jenis'], $data['tahun_anggaran_id'], $data['bulan'], $data['triwulan_id']);
            $data['status'] = 'diproses';

            $laporan = LaporanKinerja::create($data);

            GenerateLaporanKinerjaJob::dispatch($laporan->id);

            return $laporan;
        });
    }
}
