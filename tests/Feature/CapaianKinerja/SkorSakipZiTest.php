<?php

use App\Support\SkorSakipZi;

it('menghitung skor sebagai rata-rata SAKIP dan ZI', function () {
    expect(SkorSakipZi::hitung('BB', 'WBK'))->toBe(80.0)
        ->and(SkorSakipZi::hitung('AA', 'WBBM'))->toBe(95.0)
        ->and(SkorSakipZi::hitung('C', 'menuju_wbk'))->toBe(55.0);
});

it('skor CC dan C sesuai keputusan final §11: CC=50, C=30', function () {
    expect(SkorSakipZi::hitung('CC', 'belum_diterbitkan'))->toBe(25.0)
        ->and(SkorSakipZi::hitung('C', 'belum_diterbitkan'))->toBe(15.0);
});

it('opsi belum_diterbitkan bernilai 0 untuk SAKIP maupun ZI', function () {
    expect(SkorSakipZi::hitung('belum_diterbitkan', 'belum_diterbitkan'))->toBe(0.0);
});

it('opsiSakip() dan opsiZi() mengembalikan seluruh kunci yang valid', function () {
    expect(SkorSakipZi::opsiSakip())->toContain('AA', 'A', 'BB', 'B', 'CC', 'C', 'belum_diterbitkan')
        ->and(SkorSakipZi::opsiZi())->toContain('WBBM', 'WBK', 'menuju_wbk', 'belum_diterbitkan');
});
