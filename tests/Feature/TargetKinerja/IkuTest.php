<?php

it('menghasilkan kode berdasarkan nomor sasaran dan urutan IKU', function () {
    $tahun = makeTahunAnggaran();
    $sasaran = makeSasaranKegiatan($tahun); // kode: s.1

    $iku = makeIku($sasaran);

    expect($iku->kode)->toBe('[iku 1.1]');
});

it('menghasilkan kode IKK dengan prefix jenis huruf kecil', function () {
    $tahun = makeTahunAnggaran();
    $sasaran = makeSasaranKegiatan($tahun);

    $ikk = makeIku($sasaran, ['jenis' => 'IKK', 'deskripsi' => 'Deskripsi IKK']);

    expect($ikk->kode)->toBe('[ikk 1.1]');
});

it('menaikkan urutan IKU per sasaran kegiatan (Rule D-3)', function () {
    $tahun = makeTahunAnggaran();
    $sasaran = makeSasaranKegiatan($tahun);

    makeIku($sasaran, ['deskripsi' => 'A']);
    $kedua = makeIku($sasaran, ['deskripsi' => 'B']);

    expect($kedua->kode)->toBe('[iku 1.2]');
});

it('mengekstrak atribut nomor dari kode', function () {
    $tahun = makeTahunAnggaran();
    $sasaran = makeSasaranKegiatan($tahun);

    $iku = makeIku($sasaran);

    expect($iku->nomor)->toBe('1.1');
});