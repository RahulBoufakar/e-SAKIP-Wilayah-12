<?php

namespace App\Services;

use App\Models\CapaianKinerja;
use App\Models\PtsAkreditasi;
use Illuminate\Support\Facades\DB;

/**
 * TDD Sinkronisasi PTS §7. Menyalin snapshot pts_akreditasi (hasil sinkronisasi,
 * bukan panggilan API) ke komponen Akreditasi sebagai draft. Komponen Penggabungan
 * tidak disentuh.
 */
class AkreditasiImportService
{
    private const TERKUNCI = ['menunggu_validasi', 'disetujui'];

    /**
     * @return array{total_sumber: int, baru: int, diperbarui: int, tidak_berubah: int, terkunci: int, kedaluwarsa: int, tidak_lengkap: int, manual_dilewati: array<int, string>}
     */
    public function jalankan(CapaianKinerja $capaian): array
    {
        $hasil = [
            'total_sumber' => 0, 'baru' => 0, 'diperbarui' => 0, 'tidak_berubah' => 0,
            'terkunci' => 0, 'kedaluwarsa' => 0, 'tidak_lengkap' => 0, 'manual_dilewati' => [],
        ];
        $driver = config('pddikti.driver');

        $sumber = PtsAkreditasi::with('pts')
            ->whereHas('pts', fn ($q) => $q->tampil()->where('status_pts', 'aktif'))
            ->orderBy('id')
            ->get();

        $hasil['total_sumber'] = $sumber->count();
        if ($sumber->isEmpty()) {
            return $hasil;
        }

        DB::transaction(function () use ($capaian, $sumber, $driver, &$hasil) {
            // Kunci header: klik ganda menunggu di sini, lalu melihat hasil klik pertama.
            CapaianKinerja::whereKey($capaian->id)->lockForUpdate()->firstOrFail();

            $ada = $capaian->akreditasiPts()->get()->keyBy('pts_id');
            $hariIni = today();

            foreach ($sumber as $a) {
                // Kolom no_sk & masa_berlaku di capaian_akreditasi_pts NOT NULL.
                if (blank($a->no_sk) || ! $a->tgl_akhir) {
                    $hasil['tidak_lengkap']++;
                    continue;
                }

                // Masa Berlaku akreditasi sudah kadaluarsa: dilewati.
                if ($a->status === 'Kedaluwarsa') {
                    $hasil['kedaluwarsa']++;
                    continue;
                }else {
                    // masa berlaku akreditasi sudah lewat = dilewati.
                    if ($a->tgl_akhir->lt($hariIni)) {
                        $hasil['kedaluwarsa']++;
                        continue;
                    }
                }

                $data = [
                    'akreditasi' => $a->peringkat,
                    'no_sk' => $a->no_sk,
                    'masa_berlaku' => $a->tgl_akhir->toDateString(),
                    'sumber' => $driver,
                ];

                $baris = $ada->get($a->pts_id);

                if (! $baris) {
                    $capaian->akreditasiPts()->create($data + [
                        'pts_id' => $a->pts_id,
                        'file_bukti_dukung' => null,
                        'sumber_baris_id' => null,
                        'status_validasi' => 'draft',
                    ]);
                    $hasil['baru']++;
                    continue;
                }

                if (in_array($baris->status_validasi, self::TERKUNCI, true)) {
                    $hasil['terkunci']++;
                    continue;
                }

                // Draft/ditolak isian Tim Kerja: jangan ditimpa diam-diam, laporkan.
                if ($baris->sumber === 'manual') {
                    $hasil['manual_dilewati'][] = $a->pts->nama_pts;
                    continue;
                }

                $sama = $baris->akreditasi === $data['akreditasi']
                    && $baris->no_sk === $data['no_sk']
                    && $baris->masa_berlaku->toDateString() === $data['masa_berlaku']
                    && $baris->sumber === $data['sumber'];
                if ($sama) {
                    $hasil['tidak_berubah']++;
                    continue;
                }

                // Putus tautan migrasi: baris ini kini milik triwulan ini (pola sama dengan edit).
                // file_bukti_dukung sengaja tidak disentuh (bisa dipakai bersama triwulan lain).
                $baris->update($data + ['sumber_baris_id' => null]);
                $hasil['diperbarui']++;
            }
        });

        return $hasil;
    }
}