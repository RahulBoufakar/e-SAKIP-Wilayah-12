<?php

namespace App\Services;

use App\Models\Iku;
use App\Models\LaporanKegiatan;
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
    public function ownsIku(User $user, ?Iku $iku): bool
    {
        if (! $iku) {
            return false;
        }

        return $user->timKerja()
            ->whereIn('tim_kerja.id', $iku->timKerja->pluck('id'))
            ->exists();
    }

    public function ownsUsulan(User $user, ?UsulanProgramKerja $usulan): bool
    {
        if (! $usulan) {
            return false;
        }

        return $this->ownsIku($user, $usulan->iku);
    }

    public function ownsProgramKerja(User $user, ?ProgramKerja $programKerja): bool
    {
        if (! $programKerja) {
            return false;
        }

        return $this->ownsUsulan($user, $programKerja->usulanProgramKerja);
    }

    public function ownsLaporanKegiatan(User $user, ?LaporanKegiatan $laporanKegiatan): bool
    {
        if (! $laporanKegiatan) {
            return false;
        }

        return $this->ownsProgramKerja($user, $laporanKegiatan->proker);
    }
}
