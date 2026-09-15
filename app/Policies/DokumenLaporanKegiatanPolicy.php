<?php

namespace App\Policies;

use App\Models\DokumenLaporanKegiatan;
use App\Models\User;

class DokumenLaporanKegiatanPolicy
{
    public function validasi(User $user, DokumenLaporanKegiatan $dokumen): bool
    {
        return $user->hasAnyRole(['validator', 'admin', 'super_admin'])
            && filled($dokumen->file_dokumen)
            && ! $dokumen->laporan->is_locked;
    }

    // AUDIT § B4: Validator\ProgramKerja\DokumenLaporanKegiatanFileController —
    // akses dokumen laporan kegiatan lintas-tim.
    public function viewAsValidator(User $user, DokumenLaporanKegiatan $dokumen): bool
    {
        return $user->hasAnyRole(['validator', 'admin', 'super_admin']);
    }
}
