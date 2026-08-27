<?php

namespace App\Formulas;

class Iku33FasilitasiPenelitianFormula implements FormulaInterface
{
    public function label(): string
    {
        return 'IKU 3.3 — Fasilitasi Peningkatan Kinerja Penelitian/Publikasi/PkM/Kemitraan';
    }

    public function description(): string
    {
        return '(n ÷ t) × 100%. n = Jumlah PTS yang memperoleh fasilitasi peningkatan kinerja penelitian/publikasi/PkM/kemitraan, t = Total publikasi di seluruh PTS wilayah kerja.';
    }

    public function variables(): array
    {
        return [
            ['key' => 'n', 'label' => 'Jumlah PTS yang memperoleh fasilitasi penelitian/publikasi/PkM/kemitraan'],
            ['key' => 't', 'label' => 'Total publikasi di seluruh PTS wilayah kerja'],
        ];
    }

    public function calculate(array $nilai): float
    {
        return $nilai['t'] > 0 ? round(($nilai['n'] / $nilai['t']) * 100, 2) : 0;
    }
}