<?php

namespace Database\Seeders;

use App\Models\TemplateDokumen;
use Illuminate\Database\Seeder;

class TemplateDokumenSeeder extends Seeder
{
    private const DATA = [
        'rab_excel' => 'Template RAB (Excel)',
        'rab_pdf' => 'Template RAB (PDF)',
        'kak_tor' => 'Template KAK/TOR (Word)',
    ];

    public function run(): void
    {
        foreach (self::DATA as $kode => $nama) {
            TemplateDokumen::firstOrCreate(['kode' => $kode], ['nama' => $nama]);
        }
    }
}