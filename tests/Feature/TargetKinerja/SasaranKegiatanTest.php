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

// --- AUDIT § A5.2: lock generator kode — verifikasi struktural (bukan true-concurrency) ---

it('menghasilkan kode Sasaran Kegiatan yang selalu unik dan berurutan saat dibuat berturut-turut cepat pada tahun anggaran yang sama', function () {
    $tahun = makeTahunAnggaran();

    $kodeList = [];
    for ($i = 1; $i <= 10; $i++) {
        $sasaran = makeSasaranKegiatan($tahun, "Sasaran ke-{$i}");
        $kodeList[] = $sasaran->kode;
    }

    expect($kodeList)->toBe([
        's.1', 's.2', 's.3', 's.4', 's.5', 's.6', 's.7', 's.8', 's.9', 's.10',
    ])->and(count(array_unique($kodeList)))->toBe(10);
});
