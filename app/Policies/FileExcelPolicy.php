<?php

namespace App\Policies;

use App\Models\FileExcel;
use App\Models\User;

class FileExcelPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, FileExcel $fileExcel): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    // Koreksi header/footer/nama kelompok
    public function update(User $user, FileExcel $fileExcel): bool
    {
        return $user->hasRole('admin');
    }

    public function activate(User $user, FileExcel $fileExcel): bool
    {
        return $user->hasRole('admin');
    }

    // Tim kerja membuat pratinjau/unduhan template RAB dari file master aktif (RabReportController).
    public function generate(User $user): bool
    {
        return $user->hasRole('tim_kerja');
    }
}
