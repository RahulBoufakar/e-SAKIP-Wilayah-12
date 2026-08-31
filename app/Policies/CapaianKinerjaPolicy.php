<?php

namespace App\Policies;

use App\Models\CapaianKinerja;
use App\Models\User;

class CapaianKinerjaPolicy
{
    public function approve(User $user, CapaianKinerja $capaianKinerja): bool
    {
        return $user->hasAnyRole(['validator', 'admin', 'super_admin'])
            && $capaianKinerja->status === 'menunggu_validasi'
            && $capaianKinerja->isDataLengkap();
    }

    public function reject(User $user, CapaianKinerja $capaianKinerja): bool
    {
        return $user->hasAnyRole(['validator', 'admin', 'super_admin'])
            && $capaianKinerja->status === 'menunggu_validasi';
    }
}