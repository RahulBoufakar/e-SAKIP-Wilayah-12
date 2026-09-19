<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\DB;

/**
 * AUDIT-KEAMANAN-DAN-TECHNICAL-DEBT.md § A5.1.
 *
 * Mekanisme lock bersama untuk transisi status pada model state-machine
 * (UsulanProgramKerja, PelaporanKegiatan, CapaianKinerja, AnalisaKinerja).
 * Sengaja dipisah dari HasStatusPengiriman/vocabulary status masing-masing
 * model, supaya bisa dipakai baik oleh model dengan kolom `status` maupun
 * `status_validasi` tanpa memaksa unifikasi enum.
 */
trait LocksRowForTransition
{
    /**
     * Ambil baris terbaru dengan row-lock dalam transaksi, lalu jalankan
     * callback di dalamnya. Mencegah race condition pada pola
     * "baca status -> cek -> tulis" yang sebelumnya tidak atomik (mis. dua
     * klik "Setujui" hampir bersamaan pada baris yang sama).
     */
    protected function transitionWithLock(callable $callback)
    {
        return DB::transaction(function () use ($callback) {
            $fresh = static::whereKey($this->getKey())->lockForUpdate()->firstOrFail();

            return $callback($fresh);
        });
    }
}
