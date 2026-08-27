<?php

namespace App\Formulas;

class Iku21FasilitasiMutuFormula implements FormulaInterface
{
    public function label(): string
    {
        return 'IKU 2.1 — Fasilitasi Peningkatan Mutu PTS';
    }

    public function description(): string
    {
        return '(n ÷ t) × 100%. n = Jumlah PTS yang menerima fasilitasi peningkatan mutu (pembelajaran, SPMI, dll.), t = Total PTS di wilayah kerja.';
    }

    public function variables(): array
    {
        return [
            ['key' => 'n', 'label' => 'Jumlah PTS yang menerima fasilitasi peningkatan mutu'],
            ['key' => 't', 'label' => 'Total PTS di wilayah kerja'],
        ];
    }

    public function calculate(array $nilai): float
    {
        return $nilai['t'] > 0 ? round(($nilai['n'] / $nilai['t']) * 100, 2) : 0;
    }
}