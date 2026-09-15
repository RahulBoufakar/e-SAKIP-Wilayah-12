<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, User $model): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    // AUDIT § B2: admin hanya boleh mengubah akun admin miliknya sendiri.
    // Mengubah admin LAIN hanya boleh lewat super_admin (Gate::before di
    // AppServiceProvider melewati method ini sepenuhnya untuk super_admin).
    public function update(User $user, User $model): bool
    {
        if ($model->hasRole('admin') && $user->id !== $model->id) {
            return false;
        }

        return $user->hasRole('admin');
    }

    // FR-M3: user berrole admin tidak boleh dihapus lewat aksi ini.
    public function delete(User $user, User $model): bool
    {
        return $user->hasRole('admin') && ! $model->hasRole('admin');
    }
}
