<?php

use App\Models\Triwulan;

it('true hanya saat triwulan yang diberikan aktif untuk tahun tersebut', function () {
    $tahun = makeTahunAnggaran();
    activateTriwulan($tahun, 'TW2');

    expect(isTriwulanAktif('TW2', $tahun->tahun))->toBeTrue()
        ->and(isTriwulanAktif('TW1', $tahun->tahun))->toBeFalse();
});

it('menerima triwulan_id maupun kode', function () {
    $tahun = makeTahunAnggaran();
    $tw3Id = Triwulan::where('kode', 'TW3')->value('id');
    activateTriwulan($tahun, 'TW3');

    expect(isTriwulanAktif($tw3Id, $tahun->tahun))->toBeTrue();
});

it('tidak case-sensitive untuk pencarian kode', function () {
    $tahun = makeTahunAnggaran();
    activateTriwulan($tahun, 'TW1');

    expect(isTriwulanAktif('tw1', $tahun->tahun))->toBeTrue();
});

it('false untuk tahun yang tidak ada', function () {
    expect(isTriwulanAktif('TW1', 2999))->toBeFalse();
});