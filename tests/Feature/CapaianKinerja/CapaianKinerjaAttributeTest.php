<?php

beforeEach(function () {
    $this->tahun = makeTahunAnggaran();
    $sasaran = makeSasaranKegiatan($this->tahun);
    $this->iku = makeIku($sasaran); // tanpa formula_kode
    $this->ikuFormula = makeIku($sasaran, ['deskripsi' => 'IKU dengan formula', 'formula_kode' => 'iku_1_1_kepuasan']);
});

it('capaian null saat target atau realisasi kosong', function () {
    $capaian = makeCapaianKinerja($this->iku, $this->tahun, ['target' => null, 'realisasi' => null]);

    expect($capaian->capaian)->toBeNull();
});

it('data dianggap lengkap tanpa formula selama realisasi terisi', function () {
    $capaian = makeCapaianKinerja($this->iku, $this->tahun, ['realisasi' => 10]);

    expect($capaian->isDataLengkap())->toBeTrue();
});

it('data dianggap tidak lengkap tanpa formula saat realisasi kosong', function () {
    $capaian = makeCapaianKinerja($this->iku, $this->tahun, ['realisasi' => null]);

    expect($capaian->isDataLengkap())->toBeFalse();
});

it('mewajibkan setiap variabel formula terisi saat IKU memakai formula', function () {
    $capaian = makeCapaianKinerja($this->ikuFormula, $this->tahun, [
        'realisasi' => 50,
        'variabel' => ['n' => 5],
    ]);

    expect($capaian->isDataLengkap())->toBeFalse();

    $capaian->variabel = ['n' => 5, 't' => 10];
    $capaian->save();

    expect($capaian->fresh()->isDataLengkap())->toBeTrue();
});

it('can_kirim true hanya saat data lengkap, ada dokumen, dan status mengizinkan', function () {
    $capaian = makeCapaianKinerja($this->iku, $this->tahun, ['realisasi' => 80]);

    expect($capaian->can_kirim)->toBeFalse(); // belum ada dokumen

    $capaian->dokumen()->create(['nama_dokumen' => 'Bukti', 'file_dokumen' => 'bukti.pdf']);

    expect($capaian->fresh()->can_kirim)->toBeTrue();
});