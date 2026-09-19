<?php

namespace App\Policies;

use App\Models\ProgramKerja;
use App\Models\User;
use App\Services\TeamOwnershipService;

class ProgramKerjaPolicy
{
    public function __construct(private TeamOwnershipService $teamOwnership)
    {
    }

    // AUDIT § B3: cek kepemilikan tim didelegasikan ke TeamOwnershipService.
    public function view(User $user, ProgramKerja $programKerja): bool
    {
        return $user->hasRole('tim_kerja') && $this->teamOwnership->ownsProgramKerja($user, $programKerja);
    }
}
