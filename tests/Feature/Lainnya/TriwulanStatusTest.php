<?php

use App\Models\Triwulan;
use App\Models\TriwulanStatus;

it('mengaktifkan tepat satu triwulan per tahun anggaran dan menonaktifkan sisanya (Rule R-1)', function () {
    $tahun = makeTahunAnggaran();
    $tw1 = Triwulan::where('kode', 'TW1')->value('id');
    $tw2 = Triwulan::where('kode', 'TW2')->value('id');

    TriwulanStatus::activate($tw1, $tahun->id);
    TriwulanStatus::activate($tw2, $tahun->id);

    expect(TriwulanStatus::where('tahun_anggaran_id', $tahun->id)->where('triwulan_id', $tw1)->value('status'))->toBe('non_aktif')
        ->and(TriwulanStatus::where('tahun_anggaran_id', $tahun->id)->where('triwulan_id', $tw2)->value('status'))->toBe('aktif');
});

it('menonaktifkan semua triwulan saat activate() dipanggil dengan id 0', function () {
    $tahun = makeTahunAnggaran();
    activateTriwulan($tahun, 'TW1');

    TriwulanStatus::activate(0, $tahun->id);

    expect(TriwulanStatus::where('tahun_anggaran_id', $tahun->id)->where('status', 'aktif')->count())->toBe(0);
});

it('tidak memengaruhi status triwulan milik tahun anggaran lain', function () {
    $tahunA = makeTahunAnggaran(2026);
    $tahunB = makeTahunAnggaran(2027);

    activateTriwulan($tahunA, 'TW1');
    activateTriwulan($tahunB, 'TW1');

    TriwulanStatus::activate(0, $tahunA->id);

    expect(TriwulanStatus::where('tahun_anggaran_id', $tahunA->id)->where('status', 'aktif')->count())->toBe(0)
        ->and(TriwulanStatus::where('tahun_anggaran_id', $tahunB->id)->where('status', 'aktif')->count())->toBe(1);
});