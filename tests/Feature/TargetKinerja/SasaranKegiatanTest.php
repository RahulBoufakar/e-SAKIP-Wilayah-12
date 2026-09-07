<?php

it('menghasilkan kode s.1 untuk sasaran kegiatan pertama pada suatu tahun anggaran', function () {
    $tahun = makeTahunAnggaran();

    $sasaran = makeSasaranKegiatan($tahun);

    expect($sasaran->kode)->toBe('s.1');
});

it('menaikkan kode secara berurutan dalam tahun anggaran yang sama', function () {
    $tahun = makeTahunAnggaran();

    $pertama = makeSasaranKegiatan($tahun, 'A');
    $kedua = makeSasaranKegiatan($tahun, 'B');

    expect($pertama->kode)->toBe('s.1')
        ->and($kedua->kode)->toBe('s.2');
});

it('mereset penomoran kode per tahun anggaran (Rule D-2)', function () {
    $tahun2026 = makeTahunAnggaran(2026);
    $tahun2027 = makeTahunAnggaran(2027);

    makeSasaranKegiatan($tahun2026, 'A');
    $sasaranTahunBaru = makeSasaranKegiatan($tahun2027, 'B');

    expect($sasaranTahunBaru->kode)->toBe('s.1');
});