<?php

use App\Formulas\FormulaRegistry;

it('resolve() mengembalikan instance formula untuk kode yang terdaftar', function () {
    $formula = FormulaRegistry::resolve('iku_1_1_kepuasan');

    expect($formula)->not->toBeNull()
        ->and($formula->label())->toContain('Kepuasan Layanan');
});

it('resolve() mengembalikan null untuk kode kosong atau tidak dikenal', function () {
    expect(FormulaRegistry::resolve(null))->toBeNull()
        ->and(FormulaRegistry::resolve('tidak_ada'))->toBeNull();
});

it('menghitung formula rasio sebagai (n / t) x 100 dibulatkan 2 desimal', function () {
    $formula = FormulaRegistry::resolve('iku_2_1_fasilitasi_mutu');

    expect($formula->calculate(['n' => 1, 't' => 3]))->toBe(33.33);
});

it('mengembalikan 0 alih-alih membagi dengan nol saat penyebut kosong', function () {
    $formula = FormulaRegistry::resolve('iku_1_1_kepuasan');

    expect($formula->calculate(['n' => 5, 't' => 0]))->toBe(0.0);
});

it('resolveByNomor() memetakan nomor IKU ke kode formula', function () {
    expect(FormulaRegistry::resolveByNomor('1.1'))->toBe('iku_1_1_kepuasan')
        ->and(FormulaRegistry::resolveByNomor('9.9'))->toBeNull();
});

it('formula SAKIP/ZI menjumlahkan dua nilai tanpa rasio', function () {
    $formula = FormulaRegistry::resolve('iku_1_3_sakip_zi');

    expect($formula->calculate(['sakip' => 80, 'zi' => 90]))->toBe(85.0);
});