<?php

namespace App\Policies;

use App\Models\Pts;
use App\Models\User;

class PtsPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, Pts $pts): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Pts $pts): bool
    {
        return $user->hasRole('admin');
    }

    // Baris bersumber API (lldikti12/pddikti) akan dibuat ulang oleh sinkronisasi
    // berikutnya, jadi tidak boleh dihapus. Hanya baris manual yang boleh.
    public function delete(User $user, Pts $pts): bool
    {
        return $user->hasRole('admin') && $pts->sumber_data === 'manual';
    }

    public function sync(User $user): bool
    {
        return $user->hasRole('admin');
    }
}