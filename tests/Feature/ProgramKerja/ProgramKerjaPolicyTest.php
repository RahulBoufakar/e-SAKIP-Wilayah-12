<?php

use App\Models\ProgramKerja;

beforeEach(function () {
    $this->tahun = makeTahunAnggaran();
    $sasaran = makeSasaranKegiatan($this->tahun);
    $this->iku = makeIku($sasaran);
    $this->tim = makeTimKerja();
    $this->iku->timKerja()->attach($this->tim->id);
});

it('mengizinkan tim_kerja yang tim-nya terhubung ke IKU lewat pivot iku_tim_kerja', function () {
    $user = userWithRole('tim_kerja');
    $user->timKerja()->attach($this->tim->id);

    $validator = userWithRole('validator', ['email' => 'validator-pk@test.local']);
    $usulan = makeUsulan($this->iku, ['status_validasi' => 'menunggu_validasi']);
    $usulan->setujui($validator->id);

    $proker = ProgramKerja::where('usulan_program_kerja_id', $usulan->id)->first();

    expect($user->can('view', $proker))->toBeTrue();
});

it('menolak tim_kerja yang tim-nya tidak terhubung ke IKU', function () {
    $lainTim = makeTimKerja('Tim Lain');
    $user = userWithRole('tim_kerja', ['email' => 'lain@test.local']);
    $user->timKerja()->attach($lainTim->id);

    $validator = userWithRole('validator', ['email' => 'validator-pk2@test.local']);
    $usulan = makeUsulan($this->iku, ['status_validasi' => 'menunggu_validasi']);
    $usulan->setujui($validator->id);

    $proker = ProgramKerja::where('usulan_program_kerja_id', $usulan->id)->first();

    expect($user->can('view', $proker))->toBeFalse();
});