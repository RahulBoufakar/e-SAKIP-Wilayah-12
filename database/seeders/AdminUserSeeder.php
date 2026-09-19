<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'admin@esakip-lldikti12.test');

        // keputusan-sadar: kredensial ini HANYA untuk testing & first-deploy.
        // WAJIB dirotasi/diganti manual oleh tim ops setelah deployment pertama
        // selesai. Lihat AUDIT-KEAMANAN-DAN-TECHNICAL-DEBT.md § A2.
        $password = Hash::make('admin123'); // Ganti dengan password yang diinginkan

        $user = User::firstOrCreate(
            ['email' => $email],
            ['name' => 'Administrator', 'password' => $password]
        );

        $user->syncRoles(['admin']);
    }
}
