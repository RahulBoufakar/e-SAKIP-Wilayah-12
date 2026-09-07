<?php

use App\Models\LaporanKegiatan;

beforeEach(function () {
    $tahun = makeTahunAnggaran();
    $sasaran = makeSasaranKegiatan($tahun);
    $iku = makeIku($sasaran);
    $usulan = makeUsulan($iku, ['status_validasi' => 'menunggu_validasi']);
    $validator = userWithRole('validator');
    $usulan->setujui($validator->id);

    $this->laporan = LaporanKegiatan::create(['proker_id' => $usulan->programKerja->id]);
});

it('belum dianggap lengkap disetujui jika belum ada dokumen', function () {
    expect($this->laporan->semua_dokumen_disetujui)->toBeFalse();
});

it('lengkap disetujui hanya jika seluruh dokumen berstatus disetujui', function () {
    $this->laporan->dokumen()->create(['nama_dokumen' => 'Dok 1', 'status_validasi' => 'disetujui']);
    $this->laporan->dokumen()->create(['nama_dokumen' => 'Dok 2', 'status_validasi' => 'menunggu_validasi']);

    expect($this->laporan->fresh()->semua_dokumen_disetujui)->toBeFalse();

    $this->laporan->dokumen()->where('nama_dokumen', 'Dok 2')->update(['status_validasi' => 'disetujui']);

    expect($this->laporan->fresh()->semua_dokumen_disetujui)->toBeTrue();
});

it('dokumen dianggap terkunci hanya saat statusnya disetujui', function () {
    $dokumen = $this->laporan->dokumen()->create(['nama_dokumen' => 'Dok Uji', 'status_validasi' => 'menunggu_validasi']);

    expect($dokumen->isLocked())->toBeFalse();

    $dokumen->update(['status_validasi' => 'disetujui']);

    expect($dokumen->fresh()->isLocked())->toBeTrue();
});