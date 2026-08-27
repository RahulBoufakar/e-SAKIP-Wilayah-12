<?php

namespace App\Formulas;

class Iku12AkreditasiFormula implements FormulaInterface
{
    public function label(): string
    {
        return 'IKU 1.2 — Akreditasi & Penggabungan PTS';
    }

    public function description(): string
    {
        return '((a + b) ÷ t) × 100%. a = Jumlah PTS terakreditasi, b = Jumlah PTS penyatuan/penggabungan, t = Total PTS di wilayah kerja.';
    }

    public function variables(): array
    {
        return [
            ['key' => 'a', 'label' => 'Jumlah PTS terakreditasi'],
            ['key' => 'b', 'label' => 'Jumlah PTS penyatuan/penggabungan'],
            ['key' => 't', 'label' => 'Total PTS di wilayah kerja'],
        ];
    }

    public function calculate(array $nilai): float
    {
        return $nilai['t'] > 0 ? round((($nilai['a'] + $nilai['b']) / $nilai['t']) * 100, 2) : 0;
    }
}