<?php

namespace App\Policies;

use App\Models\UsulanProgramKerja;
use App\Models\User;

class UsulanProgramKerjaPolicy
{
    public function view(User $user, UsulanProgramKerja $usulan): bool
    {
        return $this->owns($user, $usulan);
    }

    public function update(User $user, UsulanProgramKerja $usulan): bool
    {
        return $this->owns($user, $usulan);
    }

    private function owns(User $user, UsulanProgramKerja $usulan): bool
    {
        return $user->hasRole('tim_kerja')
            && $user->timKerja()->whereKey($usulan->iku->tim_kerja_id)->exists();
    }

    // Validator\ProgramKerja\UsulanProgramKerjaController::setujui
    public function approve(User $user, UsulanProgramKerja $usulan): bool
    {
        return $user->hasAnyRole(['validator', 'admin', 'super_admin'])
            && $usulan->status_validasi === 'menunggu_validasi';
    }

    // Validator\ProgramKerja\UsulanProgramKerjaController::tolak
    public function reject(User $user, UsulanProgramKerja $usulan): bool
    {
        return $this->approve($user, $usulan);
    }
}