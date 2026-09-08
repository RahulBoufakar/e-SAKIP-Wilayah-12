<?php

use App\Events\ActivityOccurred;
use Illuminate\Support\Facades\Cache;

it('menghapus cache context_tahun_list dan dashboard saat ActivityOccurred terpicu', function () {
    $admin = userWithRole('admin');
    $tahun = makeTahunAnggaran();

    Cache::put('context_tahun_list', collect(['dummy']), 1800);
    Cache::put("admin_dashboard_v5_{$tahun->id}", ['dummy'], 300);
    Cache::put("validator_dashboard_v2_{$tahun->id}", ['dummy'], 300);

    event(new ActivityOccurred(
        subject: $tahun,
        description: 'aksi uji coba',
        causer: $admin,
    ));

    expect(Cache::has('context_tahun_list'))->toBeFalse()
        ->and(Cache::has("admin_dashboard_v5_{$tahun->id}"))->toBeFalse()
        ->and(Cache::has("validator_dashboard_v2_{$tahun->id}"))->toBeFalse();
});

it('menghapus cache dashboard tim kerja untuk kombinasi tim yang relevan', function () {
    $admin = userWithRole('admin');
    $tahun = makeTahunAnggaran();
    $tim = makeTimKerja();

    Cache::put("tim_kerja_dashboard_v3_{$tahun->id}_{$tim->id}", ['dummy'], 300);

    event(new ActivityOccurred(
        subject: $tahun,
        description: 'aksi uji coba',
        causer: $admin,
    ));

    expect(Cache::has("tim_kerja_dashboard_v3_{$tahun->id}_{$tim->id}"))->toBeFalse();
});