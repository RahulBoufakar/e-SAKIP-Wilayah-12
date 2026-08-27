<?php

namespace App\Formulas;

interface FormulaInterface
{
    /** Nama singkat untuk dropdown Admin, mis. "IKU 1.1 — Kepuasan Layanan" */
    public function label(): string;

    /** Penjelasan cara hitung untuk dikonfirmasi Admin sebelum simpan. */
    public function description(): string;

    /** Daftar variabel input: [['key' => 'n', 'label' => '...'], ...] */
    public function variables(): array;

    public function calculate(array $nilai): float;
}