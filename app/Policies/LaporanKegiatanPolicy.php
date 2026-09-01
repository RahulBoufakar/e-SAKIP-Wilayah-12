<?php

namespace App\Policies;

use App\Models\LaporanKegiatan;
use App\Models\User;

class LaporanKegiatanPolicy
{
    public function toggleLock(User $user, LaporanKegiatan $laporanKegiatan): bool
    {
        return $user->hasAnyRole(['validator', 'admin', 'super_admin']);
    }
}