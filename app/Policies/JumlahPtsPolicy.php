<?php

namespace App\Policies;

use App\Models\JumlahPts;
use App\Models\User;

class JumlahPtsPolicy
{
     public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, JumlahPts $jumlahPts): bool
    {
        return $user->hasRole('admin');
    }
}
