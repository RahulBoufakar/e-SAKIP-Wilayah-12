<?php

namespace App\Policies;

use App\Models\JumlahMahasiswa;
use App\Models\User;

class JumlahMahasiswaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, JumlahMahasiswa $jumlahMahasiswa): bool
    {
        return $user->hasRole('admin');
    }
}