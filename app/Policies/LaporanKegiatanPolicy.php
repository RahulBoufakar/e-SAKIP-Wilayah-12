<?php

namespace App\Policies;

use App\Models\DokumenLaporanKegiatan;
use App\Models\LaporanKegiatan;
use App\Models\User;
use App\Services\TeamOwnershipService;
use Illuminate\Auth\Access\Response;

class LaporanKegiatanPolicy
{
    public function __construct(private TeamOwnershipService $teamOwnership)
    {
    }

    public function view(User $user, LaporanKegiatan $laporanKegiatan): bool
    {
        return $this->owns($user, $laporanKegiatan);
    }

    public function update(User $user, LaporanKegiatan $laporanKegiatan): Response
    {
        if (! $this->owns($user, $laporanKegiatan)) {
            return Response::deny();
        }

        if ($laporanKegiatan->is_locked) {
            return Response::deny('Laporan ini sudah dikunci oleh Validator dan tidak dapat diubah.');
        }

        return Response::allow();
    }

    public function uploadDokumen(User $user, LaporanKegiatan $laporanKegiatan, ?DokumenLaporanKegiatan $dokumen = null): Response
    {
        if (! $this->owns($user, $laporanKegiatan)) {
            return Response::deny();
        }

        if ($laporanKegiatan->is_locked) {
            return Response::deny('Laporan ini sudah dikunci oleh Validator dan tidak dapat diubah.');
        }

        if ($dokumen && $dokumen->isLocked()) {
            return Response::deny('Dokumen ini sudah disetujui dan tidak dapat diubah.');
        }

        return Response::allow();
    }

    public function deleteDokumen(User $user, LaporanKegiatan $laporanKegiatan): Response
    {
        if (! $this->owns($user, $laporanKegiatan)) {
            return Response::deny();
        }

        if ($laporanKegiatan->is_locked) {
            return Response::deny('Laporan ini sudah dikunci oleh Validator dan tidak dapat diubah.');
        }

        return Response::allow();
    }

    public function toggleLock(User $user, LaporanKegiatan $laporanKegiatan): bool
    {
        return $user->hasAnyRole(['validator', 'admin', 'super_admin']);
    }

    // AUDIT § B3: cek kepemilikan tim didelegasikan ke TeamOwnershipService.
    private function owns(User $user, LaporanKegiatan $laporanKegiatan): bool
    {
        return $user->hasRole('tim_kerja') && $this->teamOwnership->ownsLaporanKegiatan($user, $laporanKegiatan);
    }
}