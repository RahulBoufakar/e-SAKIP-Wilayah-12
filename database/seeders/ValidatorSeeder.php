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