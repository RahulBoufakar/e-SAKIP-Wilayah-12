<?php

namespace App\Formulas;

class Iku32DosenJafungFormula implements FormulaInterface
{
    public function label(): string
    {
        return 'IKU 3.2 — Dosen PTS Naik Jabatan Fungsional';
    }

    public function description(): string
    {
        return 'Input langsung (nominal), tanpa perhitungan rasio. Nilai = Jumlah Dosen PTS yang naik jabatan fungsional.';
    }

    public function variables(): array
    {
        return [
            ['key' => 'jumlah_dosen_naik_jafung', 'label' => 'Jumlah Dosen PTS Naik Jabatan Fungsional'],
        ];
    }

    public function calculate(array $nilai): float
    {
        return (float) $nilai['jumlah_dosen_naik_jafung'];
    }
}