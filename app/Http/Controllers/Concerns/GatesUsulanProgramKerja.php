<?php

namespace App\Http\Controllers\Concerns;

use App\Models\TahunAnggaran;
use App\Models\UsulanProgramKerja;

trait GatesUsulanProgramKerja
{

    /**
     * Gate tab "Tahun Depan": Admin harus sudah membuat row Tahun Anggaran
     * untuk (tahun aktif + 1) lewat Setting Tahun sebelum Tim Kerja bisa
     * mengajukan Usulan Program Kerja untuk tahun tersebut.
     */
    protected function nextTahunAnggaranExists(int $tahunAnggaranId): bool
    {
        $tahun = TahunAnggaran::find($tahunAnggaranId)?->tahun;

        return $tahun && TahunAnggaran::where('tahun', $tahun + 1)->exists();
    }

    /** Pastikan usulan milik IKU dari Tim Kerja user yang sedang login. */
    protected function authorizeAksesUsulan(UsulanProgramKerja $usulan): void
    {
        abort_unless($this->activeTimKerjaIds()->contains($usulan->iku->tim_kerja_id), 403);
    }
}