<?php

namespace App\Policies;

use App\Models\SasaranKegiatan;
use App\Models\User;

class SasaranKegiatanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, SasaranKegiatan $sasaran): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, SasaranKegiatan $sasaran): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, SasaranKegiatan $sasaran): bool
    {
        return $user->hasRole('admin');
    }
}