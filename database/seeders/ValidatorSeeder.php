<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class ValidatorSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat Role Validator jika belum ada
        $roleValidator = Role::firstOrCreate(['name' => 'validator']);

        // 2. Buat User Validator
        // keputusan-sadar: kredensial ini HANYA untuk testing & first-deploy.
        // WAJIB dirotasi/diganti manual oleh tim ops setelah deployment pertama
        // selesai. Lihat AUDIT-KEAMANAN-DAN-TECHNICAL-DEBT.md § A2.
        $validator = User::firstOrCreate(
            ['email' => 'validator1@lldikti12.test'],
            [
                'name' => 'validator1',
                'password' => Hash::make('validator123'),
            ]
        );

        // 3. Assign Role Spatie
        $validator->syncRoles([$roleValidator]);
    }
}
