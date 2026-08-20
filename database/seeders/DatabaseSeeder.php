<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            TahunAnggaranSeeder::class,
            TriwulanSeeder::class,
            AdminUserSeeder::class,
            SasaranKegiatanSeeder::class,
            NilaiTriwulan1Seeder::class,
            NilaiTriwulan2Seeder::class,
            TimKerjaSeeder::class,
        ]);
    }
}
