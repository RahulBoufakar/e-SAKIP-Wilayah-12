<?php

namespace App\Policies;

use App\Models\LaporanKinerja;
use App\Models\User;

class LaporanKinerjaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['pimpinan', 'admin']);
    }

    public function view(User $user, LaporanKinerja $laporanKinerja): bool
    {
        return $user->hasAnyRole(['pimpinan', 'admin']);
    }

    public function download(User $user, LaporanKinerja $laporanKinerja): bool
    {
        return $user->hasAnyRole(['pimpinan', 'admin']);
    }

    // Generate manual — Pimpinan & Admin (Admin sebagai jalur cadangan, §2.2)
    public function generate(User $user): bool
    {
        return $user->hasAnyRole(['pimpinan', 'admin']);
    }

    // delete: belum diimplementasikan — lihat PRD §9 poin 4 (Keputusan Terbuka)
}
