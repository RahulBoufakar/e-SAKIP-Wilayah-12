<?php

namespace App\Services;

use App\Jobs\GenerateLaporanKinerjaJob;
use App\Models\LaporanKinerja;

/**
 * Titik tunggal logika pembuatan Laporan Kinerja (PRD §6.1), dipakai oleh:
 * (a) command terjadwal, (b) tombol generate manual Pimpinan, (c) tombol
 * generate manual Admin. Setiap method hanya membuat record berstatus
 * 'diproses' lalu men-dispatch job async — rendering PDF terjadi di job.
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
        $data['versi'] = LaporanKinerja::nextVersi($data['jenis'], $data['tahun_anggaran_id'], $data['bulan'], $data['triwulan_id']);
        $data['status'] = 'diproses';

        $laporan = LaporanKinerja::create($data);

        GenerateLaporanKinerjaJob::dispatch($laporan->id);

        return $laporan;
    }
}
