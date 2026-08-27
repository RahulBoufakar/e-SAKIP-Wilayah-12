<?php

namespace App\Formulas;

class Iku11KepuasanFormula implements FormulaInterface
{
    public function label(): string
    {
        return 'IKU 1.1 — Kepuasan Layanan';
    }

    public function description(): string
    {
        return '(n ÷ t) × 100%. n = Jumlah responden puas, t = Total responden pengguna layanan.';
    }

    public function variables(): array
    {
        return [
            ['key' => 'n', 'label' => 'Jumlah responden puas'],
            ['key' => 't', 'label' => 'Total responden pengguna layanan'],
        ];
    }

    public function calculate(array $nilai): float
    {
        return $nilai['t'] > 0 ? round(($nilai['n'] / $nilai['t']) * 100, 2) : 0;
    }
}