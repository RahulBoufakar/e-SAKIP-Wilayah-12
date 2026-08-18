<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // super_admin ditambahkan untuk bypass semua Gate (lihat AppServiceProvider::boot),
        // termasuk buka-kunci field yang sudah 'disetujui' pada modul Tim Kerja.
        foreach (['admin', 'super_admin', 'tim_kerja', 'validator'] as $role) {
            Role::firstOrCreate(['name' => $role]);
        }
    }
}
