<?php

use App\Models\ProgramKerja;
use App\Services\TeamOwnershipService;

beforeEach(function () {
    $this->service = app(TeamOwnershipService::class);
    $this->tahun = makeTahunAnggaran();
    $this->sasaran = makeSasaranKegiatan($this->tahun);
});

it('ownsIku() false untuk user tanpa tim sama sekali', function () {
    $iku = makeIku($this->sasaran);
    $tim = makeTimKerja();
    $iku->timKerja()->attach($tim->id);

    $user = userWithRole('tim_kerja'); // tidak di-attach ke tim manapun

    expect($this->service->ownsIku($user, $iku))->toBeFalse();
});

it('ownsIku() true untuk user dengan tim yang tepat', function () {
    $iku = makeIku($this->sasaran);
    $tim = makeTimKerja();
    $iku->timKerja()->attach($tim->id);

    $user = userWithRole('tim_kerja');
    $user->timKerja()->attach($tim->id);

    expect($this->service->ownsIku($user, $iku))->toBeTrue();
});

it('ownsIku() true untuk user multi-tim selama salah satu timnya cocok', function () {
    $iku = makeIku($this->sasaran);
    $timCocok = makeTimKerja('Tim Cocok');
    $timLain = makeTimKerja('Tim Lain');
    $iku->timKerja()->attach($timCocok->id);

    $user = userWithRole('tim_kerja');
    $user->timKerja()->attach([$timLain->id, $timCocok->id]);

    expect($this->service->ownsIku($user, $iku))->toBeTrue();
});

it('ownsIku() false untuk IKU tanpa tim sama sekali, meski user punya tim lain', function () {
    $iku = makeIku($this->sasaran); // tidak di-attach tim apa pun

    $user = userWithRole('tim_kerja');
    $tim = makeTimKerja();
    $user->timKerja()->attach($tim->id);

    expect($this->service->ownsIku($user, $iku))->toBeFalse();
});

it('ownsUsulan() didelegasikan lewat IKU milik usulan tsb', function () {
    $iku = makeIku($this->sasaran);
    $tim = makeTimKerja();
    $iku->timKerja()->attach($tim->id);
    $usulan = makeUsulan($iku);

    $user = userWithRole('tim_kerja');
    $user->timKerja()->attach($tim->id);

    expect($this->service->ownsUsulan($user, $usulan))->toBeTrue();
});

it('ownsUsulan() false untuk user dari tim yang tidak bertanggung jawab atas IKU tsb', function () {
    $iku = makeIku($this->sasaran);
    $tim = makeTimKerja();
    $iku->timKerja()->attach($tim->id);
    $usulan = makeUsulan($iku);

    $timLain = makeTimKerja('Tim Lain Usulan');
    $user = userWithRole('tim_kerja', ['email' => 'usulan-outsider@test.local']);
    $user->timKerja()->attach($timLain->id);

    expect($this->service->ownsUsulan($user, $usulan))->toBeFalse();
});

it('ownsProgramKerja() didelegasikan lewat usulan & IKU-nya', function () {
    $iku = makeIku($this->sasaran);
    $tim = makeTimKerja();
    $iku->timKerja()->attach($tim->id);

    $usulan = makeUsulan($iku, ['status_validasi' => 'menunggu_validasi']);
    $validator = userWithRole('validator');
    $usulan->setujui($validator->id);

    $programKerja = ProgramKerja::where('usulan_program_kerja_id', $usulan->id)->first();

    $user = userWithRole('tim_kerja', ['email' => 'pk-owner@test.local']);
    $user->timKerja()->attach($tim->id);

    expect($this->service->ownsProgramKerja($user, $programKerja))->toBeTrue();
});

it('ownsProgramKerja() false untuk user dari tim lain', function () {
    $iku = makeIku($this->sasaran);
    $tim = makeTimKerja();
    $iku->timKerja()->attach($tim->id);

    $usulan = makeUsulan($iku, ['status_validasi' => 'menunggu_validasi']);
    $validator = userWithRole('validator');
    $usulan->setujui($validator->id);

    $programKerja = ProgramKerja::where('usulan_program_kerja_id', $usulan->id)->first();

    $timLain = makeTimKerja('Tim Lain PK');
    $user = userWithRole('tim_kerja', ['email' => 'pk-outsider@test.local']);
    $user->timKerja()->attach($timLain->id);

    expect($this->service->ownsProgramKerja($user, $programKerja))->toBeFalse();
});

it('ownsLaporanKegiatan() didelegasikan lewat proker miliknya', function () {
    $iku = makeIku($this->sasaran);
    $tim = makeTimKerja();
    $iku->timKerja()->attach($tim->id);

    $usulan = makeUsulan($iku, ['status_validasi' => 'menunggu_validasi']);
    $validator = userWithRole('validator');
    $usulan->setujui($validator->id);

    $programKerja = ProgramKerja::where('usulan_program_kerja_id', $usulan->id)->first();
    $laporan = \App\Models\LaporanKegiatan::create(['proker_id' => $programKerja->id]);

    $user = userWithRole('tim_kerja', ['email' => 'lk-owner@test.local']);
    $user->timKerja()->attach($tim->id);

    expect($this->service->ownsLaporanKegiatan($user, $laporan))->toBeTrue();
});

it('ownsLaporanKegiatan() false untuk user dari tim lain', function () {
    $iku = makeIku($this->sasaran);
    $tim = makeTimKerja();
    $iku->timKerja()->attach($tim->id);

    $usulan = makeUsulan($iku, ['status_validasi' => 'menunggu_validasi']);
    $validator = userWithRole('validator');
    $usulan->setujui($validator->id);

    $programKerja = ProgramKerja::where('usulan_program_kerja_id', $usulan->id)->first();
    $laporan = \App\Models\LaporanKegiatan::create(['proker_id' => $programKerja->id]);

    $timLain = makeTimKerja('Tim Lain LK');
    $user = userWithRole('tim_kerja', ['email' => 'lk-outsider@test.local']);
    $user->timKerja()->attach($timLain->id);

    expect($this->service->ownsLaporanKegiatan($user, $laporan))->toBeFalse();
});

