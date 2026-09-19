<?php

namespace App\Policies;

use App\Models\CapaianKinerjaDokumen;
use App\Models\User;

/**
 * AUDIT § B4: dipakai Validator\CapaianKinerja\CapaianKinerjaDokumenController
 * untuk menyatakan aturan "boleh dilihat validator" secara eksplisit, bukan
 * sekadar cek role inline di controller.
 */
class CapaianKinerjaDokumenPolicy
{
    public function viewAsValidator(User $user, CapaianKinerjaDokumen $dokumen): bool
    {
        return $user->hasAnyRole(['validator', 'admin', 'super_admin']);
    }
}
