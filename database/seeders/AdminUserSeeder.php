<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'admin@esakip-lldikti12.test');
        $password = Hash::make('admin123'); // Ganti dengan password yang diinginkan

        User::firstOrCreate(
            ['email' => $email],
            ['name' => 'Administrator', 'password' => $password, 'role' => 'administrator']
        );
    }
}
