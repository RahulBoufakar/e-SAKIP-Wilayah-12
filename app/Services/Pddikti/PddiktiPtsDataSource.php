<?php

namespace App\Services\Pddikti;

/** Stub: diisi saat akses PDDikti langsung tersedia. */
class PddiktiPtsDataSource implements PtsDataSource
{
     /**
     * Seluruh halaman PTS (status Aktif), sudah dinormalisasi.
     *
     * @return array<int, array{kode_pts: ?string, nama_pts: ?string, provinsi: ?string, status: ?string, peringkat_akreditasi: ?string, status_akreditasi: ?string}>
     *
     * @throws PddiktiApiException
     */
    public function ambilSemuaPts(): array
    {
        throw new PddiktiApiException('Driver PDDikti belum tersedia.');
    }

    public function ambilSemuaAkreditasi(): array
    {
        throw new PddiktiApiException('Driver PDDikti belum tersedia.');
    }
}