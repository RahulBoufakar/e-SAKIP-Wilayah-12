<?php

beforeEach(function () {
    $tahun = makeTahunAnggaran();
    $sasaran = makeSasaranKegiatan($tahun);
    $this->iku = makeIku($sasaran);
    $this->tim = makeTimKerja();
    $this->iku->timKerja()->attach($this->tim->id);
});

it('mengizinkan tim_kerja yang tim-nya terhubung ke IKU lewat pivot untuk melihat/mengubah usulan', function () {
    $user = userWithRole('tim_kerja');
    $user->timKerja()->attach($this->tim->id);

    $usulan = makeUsulan($this->iku);

    expect($user->can('view', $usulan))->toBeTrue()
        ->and($user->can('update', $usulan))->toBeTrue();
});

it('menolak tim_kerja yang tim-nya bukan penanggung jawab IKU tersebut', function () {
    $lainTim = makeTimKerja('Tim Lain 2');
    $user = userWithRole('tim_kerja', ['email' => 'lain2@test.local']);
    $user->timKerja()->attach($lainTim->id);

    $usulan = makeUsulan($this->iku);

    expect($user->can('view', $usulan))->toBeFalse()
        ->and($user->can('update', $usulan))->toBeFalse();
});