<?php

namespace Database\Seeders;

use App\Models\Triwulan;
use Illuminate\Database\Seeder;

class TriwulanSeeder extends Seeder
{
    public function run(): void
    {
        foreach (range(1, 4) as $urutan) {
            Triwulan::firstOrCreate(
                ['kode' => "TW{$urutan}"],
                ['urutan' => $urutan]
            );
        }
    }
}
