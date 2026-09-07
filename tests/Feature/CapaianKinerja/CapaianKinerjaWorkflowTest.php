<?php

use Illuminate\Auth\Access\AuthorizationException;

beforeEach(function () {
    $this->tahun = makeTahunAnggaran();
    $sasaran = makeSasaranKegiatan($this->tahun);
    $this->iku = makeIku($sasaran);
});

it('kirim() memindahkan status draft/ditolak ke menunggu_validasi', function () {
    $capaian = makeCapaianKinerja($this->iku, $this->tahun, ['realisasi' => 90]);

    $capaian->kirim();

    expect($capaian->status)->toBe('menunggu_validasi');
});

it('menolak setujui() jika data belum lengkap', function () {
    $capaian = makeCapaianKinerja($this->iku, $this->tahun, [
        'realisasi' => null,
        'status' => 'menunggu_validasi',
    ]);

    $capaian->setujui();
})->throws(RuntimeException::class);

it('menyetujui saat data lengkap dan status menunggu_validasi', function () {
    $capaian = makeCapaianKinerja($this->iku, $this->tahun, [
        'realisasi' => 90,
        'status' => 'menunggu_validasi',
    ]);

    $this->actingAs(userWithRole('validator'));

    $capaian->setujui();

    expect($capaian->status)->toBe('disetujui');
});

it('mengunci field untuk semua role kecuali super_admin setelah disetujui', function () {
    $capaian = makeCapaianKinerja($this->iku, $this->tahun, [
        'realisasi' => 90,
        'status' => 'disetujui',
    ]);

    $this->actingAs(userWithRole('tim_kerja'));
    expect($capaian->isFieldLocked())->toBeTrue();

    $this->actingAs(userWithRole('super_admin', ['email' => 'sa@test.local']));
    expect($capaian->isFieldLocked())->toBeFalse();
});

it('tetap terkunci saat menunggu_validasi meskipun user adalah super_admin', function () {
    $capaian = makeCapaianKinerja($this->iku, $this->tahun, [
        'realisasi' => 90,
        'status' => 'menunggu_validasi',
    ]);

    $this->actingAs(userWithRole('super_admin'));

    expect($capaian->isFieldLocked())->toBeTrue();
});

it('tolak() mewajibkan catatan_revisi dan memindahkan status ke ditolak', function () {
    $capaian = makeCapaianKinerja($this->iku, $this->tahun, [
        'realisasi' => 90,
        'status' => 'menunggu_validasi',
    ]);

    $this->actingAs(userWithRole('validator'));

    $capaian->tolak('Data realisasi tidak sesuai');

    expect($capaian->status)->toBe('ditolak')
        ->and($capaian->catatan_revisi)->toBe('Data realisasi tidak sesuai');
});

it('hanya validator/admin/super_admin yang boleh menyetujui atau menolak', function () {
    $capaian = makeCapaianKinerja($this->iku, $this->tahun, [
        'realisasi' => 90,
        'status' => 'menunggu_validasi',
    ]);

    $this->actingAs(userWithRole('tim_kerja'));

    $capaian->setujui();
})->throws(AuthorizationException::class);

it('CapaianKinerjaPolicy hanya izinkan approve saat menunggu_validasi dan data lengkap', function () {
    $validator = userWithRole('validator');

    $lengkap = makeCapaianKinerja($this->iku, $this->tahun, ['realisasi' => 90, 'status' => 'menunggu_validasi']);
    $belumLengkap = makeCapaianKinerja($this->iku, $this->tahun, [
        'realisasi' => null,
        'status' => 'menunggu_validasi',
        'triwulan_id' => \App\Models\Triwulan::where('kode', 'TW2')->value('id'),
    ]);

    expect($validator->can('approve', $lengkap))->toBeTrue()
        ->and($validator->can('approve', $belumLengkap))->toBeFalse();
});

it('CapaianKinerjaPolicy izinkan reject terlepas dari kelengkapan data, asal menunggu_validasi', function () {
    $validator = userWithRole('validator');
    $capaian = makeCapaianKinerja($this->iku, $this->tahun, ['realisasi' => null, 'status' => 'menunggu_validasi']);

    expect($validator->can('reject', $capaian))->toBeTrue();
});