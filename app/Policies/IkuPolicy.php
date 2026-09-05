<?php

namespace App\Policies;

use App\Models\Iku;
use App\Models\User;

class IkuPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user): bool
    {
        return $user->hasRole('admin');
    }

    // IkuLldiktiController::updateTarget — Admin boleh isi/ubah Target PK
    // untuk Triwulan mana pun (beda dari Tim Kerja yang dibatasi triwulan aktif).
    public function manageTarget(User $user): bool
    {
        return $user->hasRole('admin');
    }

    // RencanaAksiController::update (Admin) — isi uraian Rencana Aksi 4 Triwulan
    // untuk satu IKU (Rule R-4: sengaja tanpa gate Triwulan Aktif).
    public function manageRencanaAksi(User $user, Iku $iku): bool
    {
        return $user->hasRole('admin');
    }

    // Dipakai Tim Kerja: CapaianKinerjaController & AnalisaKinerjaController (show/update/kirim/storeOrUpdate)
    public function manageKinerja(User $user, Iku $iku): bool
    {
        return $user->hasRole('tim_kerja')
            && $iku->tim_kerja_id !== null
            && $user->timKerja()->whereKey($iku->tim_kerja_id)->exists();
    }
}