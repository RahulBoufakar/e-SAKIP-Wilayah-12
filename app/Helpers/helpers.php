<?php

use App\Models\TahunAnggaran;
use App\Models\Triwulan;
use App\Models\TriwulanStatus;

if (! function_exists('isTriwulanAktif')) {
    /**
     * Cek apakah suatu Triwulan sedang aktif untuk suatu Tahun Anggaran
     * (dibaca dari Tools > Setting Triwulan / tabel triwulan_status).
     *
     * @param  int|string  $triwulan  kode ("TW1".."TW4", tidak case-sensitive) atau triwulan_id
     * @param  int  $tahun  tahun anggaran (mis. 2026); fallback ke tahun_anggaran_id jika tidak ketemu
     */
    function isTriwulanAktif(int|string $triwulan, int $tahun): bool
    {
        $triwulanId = is_numeric($triwulan)
            ? (int) $triwulan
            : Triwulan::where('kode', strtoupper($triwulan))->value('id');

        if (! $triwulanId) {
            return false;
        }

        $tahunAnggaranId = TahunAnggaran::where('tahun', $tahun)->value('id')
            ?? (TahunAnggaran::whereKey($tahun)->exists() ? $tahun : null);

        if (! $tahunAnggaranId) {
            return false;
        }

        return TriwulanStatus::where('tahun_anggaran_id', $tahunAnggaranId)
            ->where('triwulan_id', $triwulanId)
            ->where('status', 'aktif')
            ->exists();
    }
}