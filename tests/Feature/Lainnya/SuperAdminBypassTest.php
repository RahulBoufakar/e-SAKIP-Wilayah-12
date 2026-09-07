<?php

use App\Models\Pts;

it('super_admin melewati semua Gate lewat Gate::before di AppServiceProvider', function () {
    $superAdmin = userWithRole('super_admin');

    expect($superAdmin->can('viewAny', Pts::class))->toBeTrue()
        ->and($superAdmin->can('create', Pts::class))->toBeTrue()
        ->and($superAdmin->can('delete', Pts::class))->toBeTrue();
});

it('tim_kerja biasa tidak mendapat akses yang sama', function () {
    $timKerja = userWithRole('tim_kerja');

    expect($timKerja->can('viewAny', Pts::class))->toBeFalse();
});