<?php

namespace Database\Seeders;

use App\Models\TimKerja;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class TimKerjaSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat Role Tim Kerja jika belum ada
        $roleTimKerja = Role::firstOrCreate(['name' => 'tim_kerja']);

        // 2. Buat Tim Kerja "Humas"
        $timHumas = TimKerja::firstOrCreate([
            'nama_tim' => 'Humas',
        ]);

        // 3. Buat User Humas
        $userHumas = User::firstOrCreate(
            ['email' => 'humas1@lldikti12.test'],
            [
                'name' => 'humas1',
                'password' => Hash::make('humas123'),
            ]
        );

        // 4. Assign Role Spatie
        $userHumas->syncRoles([$roleTimKerja]);

        // 5. Relasikan User ke Tim Kerja Humas (Pivot: user_tim_kerja)
        $userHumas->timKerja()->syncWithoutDetaching([$timHumas->id]);

        // 6. Buat Tim Kerja "Kelembagaan" (di-assign ke IKU 1.1 lewat SasaranKegiatanSeeder)
        $timKelembagaan = TimKerja::firstOrCreate([
            'nama_tim' => 'Kelembagaan',
        ]);

        // 7. Buat User Kelembagaan
        $userKelembagaan = User::firstOrCreate(
            ['email' => 'kelembagaan1@lldikti12.test'],
            [
                'name' => 'kelembagaan1',
                'password' => Hash::make('kelembagaan123'),
            ]
        );

        // 8. Assign Role Spatie
        $userKelembagaan->syncRoles([$roleTimKerja]);

        // 9. Relasikan User ke Tim Kerja Kelembagaan (Pivot: user_tim_kerja)
        $userKelembagaan->timKerja()->syncWithoutDetaching([$timKelembagaan->id]);
    }
}