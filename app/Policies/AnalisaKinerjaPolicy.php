<?php

namespace App\Policies;

use App\Models\AnalisaKinerja;
use App\Models\TriwulanStatus;
use App\Models\User;

class AnalisaKinerjaPolicy
{
    // Konsolidasi aturan yang tadinya dicek manual di controller:
    // status menunggu_validasi + Triwulan bersangkutan sedang aktif.
    public function validasi(User $user, AnalisaKinerja $analisaKinerja): bool
    {
        if (! $user->hasAnyRole(['validator', 'admin', 'super_admin'])) {
            return false;
        }

        if ($analisaKinerja->status !== 'menunggu_validasi') {
            return false;
        }

        $triwulanAktifStatus = TriwulanStatus::where('tahun_anggaran_id', $analisaKinerja->tahun_anggaran_id)
            ->where('status', 'aktif')
            ->first();

        return $triwulanAktifStatus && $triwulanAktifStatus->triwulan_id === $analisaKinerja->triwulan_id;
    }
}