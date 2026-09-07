<?php

beforeEach(function () {
    $this->tahun = makeTahunAnggaran();
    $sasaran = makeSasaranKegiatan($this->tahun);
    $this->iku = makeIku($sasaran);
});

it('izinkan validasi hanya saat triwulan pada record sedang aktif', function () {
    activateTriwulan($this->tahun, 'TW1');
    $analisa = makeAnalisaKinerja($this->iku, $this->tahun);
    $validator = userWithRole('validator');

    expect($validator->can('validasi', $analisa))->toBeTrue();
});

it('blokir validasi saat triwulan lain yang sedang aktif', function () {
    activateTriwulan($this->tahun, 'TW2');
    $analisa = makeAnalisaKinerja($this->iku, $this->tahun); // dibuat untuk TW1

    $validator = userWithRole('validator');

    expect($validator->can('validasi', $analisa))->toBeFalse();
});

it('blokir validasi saat status bukan menunggu_validasi', function () {
    activateTriwulan($this->tahun, 'TW1');
    $analisa = makeAnalisaKinerja($this->iku, $this->tahun, ['status' => 'disetujui']);

    $validator = userWithRole('validator');

    expect($validator->can('validasi', $analisa))->toBeFalse();
});

it('blokir tim_kerja untuk memvalidasi meskipun status & triwulan cocok', function () {
    activateTriwulan($this->tahun, 'TW1');
    $analisa = makeAnalisaKinerja($this->iku, $this->tahun);

    $timKerja = userWithRole('tim_kerja');

    expect($timKerja->can('validasi', $analisa))->toBeFalse();
});