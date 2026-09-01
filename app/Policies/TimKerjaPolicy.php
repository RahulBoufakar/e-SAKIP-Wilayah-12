<?php

namespace App\Policies;

use App\Models\TimKerja;
use App\Models\User;

class TimKerjaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, TimKerja $timKerja): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, TimKerja $timKerja): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, TimKerja $timKerja): bool
    {
        return $user->hasRole('admin');
    }
}