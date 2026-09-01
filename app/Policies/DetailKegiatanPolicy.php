<?php

namespace App\Policies;

use App\Models\DetailKegiatan;
use App\Models\User;

class DetailKegiatanPolicy
{
    public function updateJenisKegiatan(User $user, DetailKegiatan $detailKegiatan): bool
    {
        return $user->hasAnyRole(['validator', 'admin', 'super_admin']);
    }
}