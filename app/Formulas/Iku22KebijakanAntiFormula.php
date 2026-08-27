<?php

namespace App\Formulas;

class Iku22KebijakanAntiFormula implements FormulaInterface
{
    public function label(): string
    {
        return 'IKU 2.2 — Kebijakan Anti Kekerasan, Narkoba, Korupsi';
    }

    public function description(): string
    {
        return '(n ÷ t) × 100%. n = Jumlah PTS yang memiliki kebijakan anti kekerasan, anti narkoba, dan anti korupsi, t = Total PTS di wilayah kerja.';
    }

    public function variables(): array
    {
        return [
            ['key' => 'n', 'label' => 'Jumlah PTS yang memiliki kebijakan anti kekerasan/narkoba/korupsi'],
            ['key' => 't', 'label' => 'Total PTS di wilayah kerja'],
        ];
    }

    public function calculate(array $nilai): float
    {
        return $nilai['t'] > 0 ? round(($nilai['n'] / $nilai['t']) * 100, 2) : 0;
    }
}