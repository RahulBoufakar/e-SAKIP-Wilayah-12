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

// --- AUDIT § A5.2: lock generator kode — verifikasi struktural (bukan true-concurrency) ---

it('menghasilkan kode IKU yang selalu unik dan berurutan saat dibuat berturut-turut cepat pada sasaran yang sama', function () {
    $tahun = makeTahunAnggaran();
    $sasaran = makeSasaranKegiatan($tahun);

    $kodeList = [];
    for ($i = 1; $i <= 10; $i++) {
        $iku = makeIku($sasaran, ['deskripsi' => "IKU ke-{$i}"]);
        $kodeList[] = $iku->kode;
    }

    expect($kodeList)->toBe([
        '[iku 1.1]', '[iku 1.2]', '[iku 1.3]', '[iku 1.4]', '[iku 1.5]',
        '[iku 1.6]', '[iku 1.7]', '[iku 1.8]', '[iku 1.9]', '[iku 1.10]',
    ])->and(count(array_unique($kodeList)))->toBe(10);
});
