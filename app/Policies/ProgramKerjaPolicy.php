<?php

namespace App\Policies;

use App\Models\ProgramKerja;
use App\Models\User;

class ProgramKerjaPolicy
{
    public function view(User $user, ProgramKerja $programKerja): bool
    {
        return $user->hasRole('tim_kerja')
            && $user->timKerja()
                ->whereIn('tim_kerja.id', $programKerja->usulanProgramKerja->iku->timKerja->pluck('id'))
                ->exists();
    }
}