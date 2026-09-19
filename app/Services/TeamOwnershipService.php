<?php

namespace App\Services;

use App\Models\Iku;
use App\Models\ProgramKerja;
use App\Models\UsulanProgramKerja;
use App\Models\User;

/**
 * AUDIT § B3: satu sumber kebenaran untuk "apakah user ini tergabung di
 * Tim Kerja yang bertanggung jawab atas resource X". Sebelumnya query pivot
 * yang sama ditulis ulang secara independen di IkuPolicy,
 * UsulanProgramKerjaPolicy, dan ProgramKerjaPolicy — kalau aturan
 * kepemilikan berubah, ketiganya harus diubah manual satu-satu.
 */
class TeamOwnershipService
{
    public function ownsIku(User $user, Iku $iku): bool
    {
        return $user->timKerja()
            ->whereIn('tim_kerja.id', $iku->timKerja->pluck('id'))
            ->exists();
    }

    public function ownsUsulan(User $user, UsulanProgramKerja $usulan): bool
    {
        return $this->ownsIku($user, $usulan->iku);
    }

    public function ownsProgramKerja(User $user, ProgramKerja $programKerja): bool
    {
        return $this->ownsUsulan($user, $programKerja->usulanProgramKerja);
    }
}
