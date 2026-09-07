<?php

beforeEach(function () {
    $tahun = makeTahunAnggaran();
    $sasaran = makeSasaranKegiatan($tahun);
    $this->iku = makeIku($sasaran); // kode iku: [iku 1.1]
});

it('menurunkan kode_proker dari nomor kode IKU dan urutan dimulai dari 1', function () {
    $validator = userWithRole('validator');
    $usulan = makeUsulan($this->iku, ['status_validasi' => 'menunggu_validasi']);

    $usulan->setujui($validator->id);

    expect($usulan->programKerja->kode_proker)->toBe('1.1.1');
});

it('menaikkan urutan untuk usulan kedua yang disetujui pada IKU & tahun yang sama', function () {
    $validator = userWithRole('validator');

    $pertama = makeUsulan($this->iku, ['status_validasi' => 'menunggu_validasi']);
    $pertama->setujui($validator->id);

    $kedua = makeUsulan($this->iku, ['status_validasi' => 'menunggu_validasi', 'nama_usulan' => 'Usulan Kedua']);
    $kedua->setujui($validator->id);

    expect($kedua->programKerja->kode_proker)->toBe('1.1.2');
});