<?php

use App\Models\Pts;

it('tampil() menyembunyikan status tutup, tetapi tetap meloloskan status NULL', function () {
    makePts('T1', ['status_pts' => 'tutup']);
    makePts('T2');
    makePts('T3', ['status_pts' => null]);
    makePts('T4', ['status_pts' => 'non_aktif']);

    expect(Pts::tampil()->orderBy('kode_pts')->pluck('kode_pts')->all())->toBe(['T2', 'T3', 'T4']);
});

it('opsiDropdown() menyertakan PTS tutup yang sudah dipakai, tanpa menambah PTS tutup lain', function () {
    $dipakai = makePts('U1', ['status_pts' => 'tutup']);
    makePts('U2', ['status_pts' => 'tutup']);
    makePts('U3');

    expect(Pts::opsiDropdown()->pluck('kode_pts')->all())->toBe(['U3'])
        ->and(Pts::opsiDropdown([$dipakai->id])->pluck('kode_pts')->sort()->values()->all())->toBe(['U1', 'U3']);
});