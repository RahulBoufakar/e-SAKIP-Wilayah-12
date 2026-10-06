<?php

use App\Services\FormulaRemapper;

function remapUji(string $rumus, array $peta): array
{
    return (new FormulaRemapper)->remap($rumus, $peta);
}

// --- T11: rantai penjumlahan sel ---

it('rantai +: memetakan tiap suku dan membuang suku yang barisnya tidak ikut', function () {
    $hasil = remapUji('=X22+X61+X108', [22 => 5, 108 => 9]);

    expect($hasil['formula'])->toBe('=X5+X9')
        ->and($hasil['tidak_terpetakan'])->toBe([])
        ->and($hasil['lintas_sheet'])->toBeFalse();
});

it('rantai +: semua suku ikut dipetakan tanpa ada yang dibuang', function () {
    expect(remapUji('=X22+X61', [22 => 5, 61 => 6])['formula'])->toBe('=X5+X6');
});

it('rantai +: menjadi =0 bila tidak ada suku tersisa', function () {
    $hasil = remapUji('=X22+X61+X108', [1 => 1]);

    expect($hasil['formula'])->toBe('=0')
        ->and($hasil['tidak_terpetakan'])->toBe([]);
});

it('rantai +: mempertahankan tanda $ pada rujukan absolut', function () {
    expect(remapUji('=$X$22+$X$61', [22 => 5, 61 => 6])['formula'])->toBe('=$X$5+$X$6');
});

// --- T11: rumus umum ---

it('SUM: memetakan awal dan akhir rentang bila semua baris ikut dan berurutan', function () {
    $hasil = remapUji('=SUM(X27:X29)', [27 => 10, 28 => 11, 29 => 12]);

    expect($hasil['formula'])->toBe('=SUM(X10:X12)')
        ->and($hasil['tidak_terpetakan'])->toBe([]);
});

it('SUM: rentang absolut tetap absolut', function () {
    expect(remapUji('=SUM($X$27:$X$29)', [27 => 10, 28 => 11, 29 => 12])['formula'])->toBe('=SUM($X$10:$X$12)');
});

it('SUM: rentang dengan baris di tengah yang tidak ikut ditandai, rumus tidak diubah', function () {
    $hasil = remapUji('=SUM(X27:X29)', [27 => 10, 29 => 11]);

    expect($hasil['formula'])->toBe('=SUM(X27:X29)')
        ->and($hasil['tidak_terpetakan'])->toBe(['X27:X29']);
});

it('SUM: rentang yang barisnya ikut tapi tidak berurutan di hasil ditandai', function () {
    $hasil = remapUji('=SUM(X27:X29)', [27 => 10, 28 => 11, 29 => 15]);

    expect($hasil['tidak_terpetakan'])->toBe(['X27:X29']);
});

it('perkalian: memetakan semua rujukan', function () {
    expect(remapUji('=I24*L24*O24', [24 => 9])['formula'])->toBe('=I9*L9*O9');
});

it('rujukan tunggal tidak dianggap rantai: yang tak terpetakan ditandai, bukan menjadi =0', function () {
    $hilang = remapUji('=X80', [1 => 1]);

    expect($hilang['formula'])->toBe('=X80')
        ->and($hilang['tidak_terpetakan'])->toBe(['X80'])
        ->and(remapUji('=X80', [80 => 3])['formula'])->toBe('=X3');
});

it('rujukan tak terpetakan pada rumus umum ditandai dan rumus dibiarkan', function () {
    $hasil = remapUji('=I24*L24', []);

    expect($hasil['formula'])->toBe('=I24*L24')
        ->and($hasil['tidak_terpetakan'])->toBe(['I24', 'L24']);
});

it('tidak menyentuh literal string dan nama fungsi yang mirip rujukan sel', function () {
    $hasil = remapUji('=IF(X22>0,"X22 total",LOG10(X22))', [22 => 4]);

    expect($hasil['formula'])->toBe('=IF(X4>0,"X22 total",LOG10(X4))')
        ->and($hasil['tidak_terpetakan'])->toBe([]);
});

// --- Lintas-sheet dan non-rumus ---

it('rumus lintas-sheet dilaporkan dan tidak diubah', function () {
    $biasa = remapUji('=Sheet2!X5+X22', [22 => 4]);
    $berkutip = remapUji("='RAB 1'!X5", [5 => 2]);

    expect($biasa['lintas_sheet'])->toBeTrue()
        ->and($biasa['formula'])->toBe('=Sheet2!X5+X22')
        ->and($berkutip['lintas_sheet'])->toBeTrue()
        ->and($berkutip['formula'])->toBe("='RAB 1'!X5");
});

it('tanda seru di dalam literal string bukan rumus lintas-sheet', function () {
    $hasil = remapUji('=IF(X22>0,"Halo!",0)', [22 => 2]);

    expect($hasil['lintas_sheet'])->toBeFalse()
        ->and($hasil['formula'])->toBe('=IF(X2>0,"Halo!",0)');
});

it('nilai yang bukan rumus dikembalikan apa adanya', function () {
    expect(remapUji('Teks X22', [22 => 1])['formula'])->toBe('Teks X22')
        ->and(remapUji('123', [])['formula'])->toBe('123');
});
