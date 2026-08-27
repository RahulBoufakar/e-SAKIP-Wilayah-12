<?php

namespace App\Formulas;

class Iku13SakipZiFormula implements FormulaInterface
{
    public function label(): string
    {
        return 'IKU 1.3 — SAKIP & Zona Integritas';
    }

    public function description(): string
    {
        return '(Nilai SAKIP + Nilai ZI) ÷ 2.';
    }

    public function variables(): array
    {
        return [
            ['key' => 'sakip', 'label' => 'Nilai SAKIP'],
            ['key' => 'zi', 'label' => 'Nilai Zona Integritas (ZI)'],
        ];
    }

    public function calculate(array $nilai): float
    {
        return round(($nilai['sakip'] + $nilai['zi']) / 2, 2);
    }
}