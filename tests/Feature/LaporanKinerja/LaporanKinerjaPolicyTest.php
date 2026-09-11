<?php

use App\Models\LaporanKinerja;

beforeEach(function () {
    $this->tahun = makeTahunAnggaran();
    $this->laporan = makeLaporanKinerja($this->tahun);
});

it('mengizinkan pimpinan dan admin melihat, mengunduh, dan generate Laporan Kinerja', function () {
    $pimpinan = userWithRole('pimpinan');
    $admin = userWithRole('admin', ['email' => 'admin-lk@test.local']);

    foreach ([$pimpinan, $admin] as $user) {
        expect($user->can('viewAny', LaporanKinerja::class))->toBeTrue()
            ->and($user->can('view', $this->laporan))->toBeTrue()
            ->and($user->can('download', $this->laporan))->toBeTrue()
            ->and($user->can('generate', LaporanKinerja::class))->toBeTrue();
    }
});

it('menolak tim_kerja dan validator mengakses Laporan Kinerja', function () {
    $timKerja = userWithRole('tim_kerja');
    $validator = userWithRole('validator', ['email' => 'validator-lk@test.local']);

    foreach ([$timKerja, $validator] as $user) {
        expect($user->can('viewAny', LaporanKinerja::class))->toBeFalse()
            ->and($user->can('view', $this->laporan))->toBeFalse()
            ->and($user->can('download', $this->laporan))->toBeFalse()
            ->and($user->can('generate', LaporanKinerja::class))->toBeFalse();
    }
});

it('super_admin melewati semua Gate lewat Gate::before', function () {
    $superAdmin = userWithRole('super_admin', ['email' => 'sa-lk@test.local']);

    expect($superAdmin->can('viewAny', LaporanKinerja::class))->toBeTrue()
        ->and($superAdmin->can('view', $this->laporan))->toBeTrue()
        ->and($superAdmin->can('download', $this->laporan))->toBeTrue()
        ->and($superAdmin->can('generate', LaporanKinerja::class))->toBeTrue();
});