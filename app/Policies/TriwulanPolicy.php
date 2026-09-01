<?php

namespace App\Policies;

use App\Models\Triwulan;
use App\Models\User;

class TriwulanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    // TriwulanController::update — aktifkan satu Triwulan (Rule R-1)
    public function activate(User $user, Triwulan $triwulan): bool
    {
        return $user->hasRole('admin');
    }

    // TriwulanController::nonaktifkanSemua — Rule R-1/R-22, tidak terikat instance
    public function deactivateAll(User $user): bool
    {
        return $user->hasRole('admin');
    }
}