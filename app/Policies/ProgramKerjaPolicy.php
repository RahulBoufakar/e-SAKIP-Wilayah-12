<?php

namespace App\Policies;

use App\Models\ProgramKerja;
use App\Models\User;

class ProgramKerjaPolicy
{
    public function view(User $user, ProgramKerja $programKerja): bool
    {
        return $user->hasRole('tim_kerja')
            && $user->timKerja()->whereKey($programKerja->usulanProgramKerja->iku->tim_kerja_id)->exists();
    }
}