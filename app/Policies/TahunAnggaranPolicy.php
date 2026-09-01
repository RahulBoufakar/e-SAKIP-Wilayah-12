<?php

namespace App\Policies;

use App\Models\TahunAnggaran;
use App\Models\User;

class TahunAnggaranPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, TahunAnggaran $tahun): bool
    {
        return $user->hasRole('admin');
    }
}