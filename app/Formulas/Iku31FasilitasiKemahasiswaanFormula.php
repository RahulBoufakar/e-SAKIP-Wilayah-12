<?php

namespace App\Formulas;

class Iku31FasilitasiKemahasiswaanFormula implements FormulaInterface
{
    public function label(): string
    {
        return 'IKU 3.1 — Fasilitasi Peningkatan Mutu Layanan Kemahasiswaan';
    }

    public function description(): string
    {
        return '(n ÷ t) × 100%. n = Jumlah PTS yang menerima fasilitasi peningkatan mutu layanan kemahasiswaan, t = Total PTS di wilayah kerja.';
    }

    public function variables(): array
    {
        return [
            ['key' => 'n', 'label' => 'Jumlah PTS yang menerima fasilitasi layanan kemahasiswaan'],
            ['key' => 't', 'label' => 'Total PTS di wilayah kerja'],
        ];
    }

    public function calculate(array $nilai): float
    {
        return $nilai['t'] > 0 ? round(($nilai['n'] / $nilai['t']) * 100, 2) : 0;
    }
}