<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class PimpinanSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat Role Pimpinan jika belum ada
        $rolePimpinan = Role::firstOrCreate(['name' => 'pimpinan']);

        // 2. Buat User Pimpinan
        $pimpinan = User::firstOrCreate(
            ['email' => 'pimpinan1@lldikti12.test'],
            [
                'name' => 'pimpinan1',
                'password' => Hash::make('pimpinan123'),
            ]
        );

        // 3. Assign Role Spatie
        $pimpinan->syncRoles([$rolePimpinan]);
    }
}
