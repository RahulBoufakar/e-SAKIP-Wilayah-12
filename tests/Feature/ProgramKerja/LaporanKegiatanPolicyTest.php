<?php

use App\Models\DokumenLaporanKegiatan;
use App\Models\LaporanKegiatan;
use App\Models\ProgramKerja;

beforeEach(function () {
    $this->tahun = makeTahunAnggaran();
    $sasaran = makeSasaranKegiatan($this->tahun);
    $this->iku = makeIku($sasaran);
    $this->tim = makeTimKerja();
    $this->iku->timKerja()->attach($this->tim->id);

    $usulan = makeUsulan($this->iku, ['status_validasi' => 'menunggu_validasi']);
    $validator = userWithRole('validator', ['email' => 'val-pol@test.local']);
    $usulan->setujui($validator->id);

    $this->proker = ProgramKerja::where('usulan_program_kerja_id', $usulan->id)->first();
    $this->laporan = LaporanKegiatan::create(['proker_id' => $this->proker->id]);

    $this->owner = userWithRole('tim_kerja', ['email' => 'pol-owner@test.local']);
    $this->owner->timKerja()->attach($this->tim->id);

    $timLain = makeTimKerja('Tim Lain Policy');
    $this->outsider = userWithRole('tim_kerja', ['email' => 'pol-outsider@test.local']);
    $this->outsider->timKerja()->attach($timLain->id);
});

it('mengizinkan tim_kerja pemilik melihat laporan kegiatan (view)', function () {
    expect($this->owner->can('view', $this->laporan))->toBeTrue();
    expect($this->outsider->can('view', $this->laporan))->toBeFalse();
});

it('mengizinkan tim_kerja pemilik mengubah laporan saat tidak dikunci (update)', function () {
    expect($this->owner->can('update', $this->laporan))->toBeTrue();
    expect($this->outsider->can('update', $this->laporan))->toBeFalse();

    $this->laporan->update(['is_locked' => true]);
    expect($this->owner->can('update', $this->laporan->fresh()))->toBeFalse();
});

it('mengizinkan upload dokumen hanya untuk tim pemilik saat laporan belum dikunci dan dokumen belum disetujui (uploadDokumen)', function () {
    $dokumen = $this->laporan->dokumen()->create([
        'nama_dokumen' => 'SK Tim Monev',
        'status_validasi' => 'belum_diunggah',
    ]);

    expect($this->owner->can('uploadDokumen', [$this->laporan, $dokumen]))->toBeTrue();
    expect($this->outsider->can('uploadDokumen', [$this->laporan, $dokumen]))->toBeFalse();

    // Jika laporan dikunci
    $this->laporan->update(['is_locked' => true]);
    expect($this->owner->can('uploadDokumen', [$this->laporan->fresh(), $dokumen]))->toBeFalse();

    $this->laporan->update(['is_locked' => false]);

    // Jika dokumen sudah berstatus disetujui
    $dokumen->update(['status_validasi' => 'disetujui']);
    expect($this->owner->can('uploadDokumen', [$this->laporan->fresh(), $dokumen->fresh()]))->toBeFalse();
});

it('mengizinkan hapus dokumen hanya untuk tim pemilik saat laporan belum dikunci (deleteDokumen)', function () {
    expect($this->owner->can('deleteDokumen', $this->laporan))->toBeTrue();
    expect($this->outsider->can('deleteDokumen', $this->laporan))->toBeFalse();

    $this->laporan->update(['is_locked' => true]);
    expect($this->owner->can('deleteDokumen', $this->laporan->fresh()))->toBeFalse();
});

it('membatasi toggleLock hanya untuk validator, admin, dan super_admin', function () {
    $validator = userWithRole('validator', ['email' => 'val-lock@test.local']);
    $admin = userWithRole('admin', ['email' => 'admin-lock@test.local']);

    expect($validator->can('toggleLock', $this->laporan))->toBeTrue();
    expect($admin->can('toggleLock', $this->laporan))->toBeTrue();
    expect($this->owner->can('toggleLock', $this->laporan))->toBeFalse();
    expect($this->outsider->can('toggleLock', $this->laporan))->toBeFalse();
});

it('controller memblokir tim non-pemilik pada endpoint storeDokumen dengan 403', function () {
    $this->actingAs($this->outsider)
        ->post(route('tim-kerja.pelaporan-kegiatan.dokumen.store', $this->laporan), [
            'dokumen_standar' => ['SK Tim Monev'],
        ])
        ->assertForbidden();
});

it('controller memblokir laporan yang terkunci pada endpoint storeDokumen dengan 403', function () {
    $this->laporan->update(['is_locked' => true]);

    $this->actingAs($this->owner)
        ->post(route('tim-kerja.pelaporan-kegiatan.dokumen.store', $this->laporan->fresh()), [
            'dokumen_standar' => ['SK Tim Monev'],
        ])
        ->assertForbidden();
});

it('controller mengizinkan tim pemilik mengupdate dokumen saat tidak dikunci', function () {
    $this->actingAs($this->owner)
        ->post(route('tim-kerja.pelaporan-kegiatan.dokumen.store', $this->laporan), [
            'dokumen_standar' => ['SK Tim Monev'],
        ])
        ->assertRedirect();

    expect($this->laporan->fresh()->dokumen()->where('nama_dokumen', 'SK Tim Monev')->exists())->toBeTrue();
});

