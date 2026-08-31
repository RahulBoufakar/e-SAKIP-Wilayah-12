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

    public function delete(User $user, Pts $pts): bool
    {
        return $user->hasRole('admin');
    }
}