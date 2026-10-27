<?php

namespace App\Services\Pddikti;

/**
 * Satu-satunya hal yang diketahui PtsSyncService tentang sumber data.
 * Pemetaan ke enum lokal & penyaringan BUKAN tugas driver (ada di PtsSyncService).
 */
interface PtsDataSource
{
    /**
     * Seluruh halaman PTS untuk setiap status di config('pddikti.status_diambil'), sudah dinormalisasi.
     *
     * @return array<int, array{kode_pts: ?string, nama_pts: ?string, provinsi: ?string, status: ?string, peringkat_aipt: ?string, status_aipt: ?string}>
     *
     * @throws PddiktiApiException
     */
    public function ambilSemuaPts(): array;

    /**
     * Seluruh halaman akreditasi, sudah dinormalisasi.
     *
     * @return array<int, array{kode_pts: ?string, peringkat: ?string, no_sk: ?string, tgl_sk: ?string, tgl_akhir: ?string, status: ?string}>
     *
     * @throws PddiktiApiException
     */
    public function ambilSemuaAkreditasi(): array;
}