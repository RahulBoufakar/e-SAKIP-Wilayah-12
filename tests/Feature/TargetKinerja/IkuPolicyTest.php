<?php

use App\Models\Iku;

it('membatasi pengelolaan master data IKU/IKK hanya untuk role admin', function () {
    $admin = userWithRole('admin');
    $timKerja = userWithRole('tim_kerja');
    $validator = userWithRole('validator');

    expect($admin->can('viewAny', Iku::class))->toBeTrue()
        ->and($admin->can('create', Iku::class))->toBeTrue()
        ->and($admin->can('update', Iku::class))->toBeTrue()
        ->and($admin->can('delete', Iku::class))->toBeTrue()
        ->and($timKerja->can('viewAny', Iku::class))->toBeFalse()
        ->and($validator->can('create', Iku::class))->toBeFalse();
});

it('membatasi pengaturan Target PK (manageTarget) hanya untuk role admin', function () {
    $admin = userWithRole('admin');
    $validator = userWithRole('validator');

    expect($admin->can('manageTarget', Iku::class))->toBeTrue()
        ->and($validator->can('manageTarget', Iku::class))->toBeFalse();
});