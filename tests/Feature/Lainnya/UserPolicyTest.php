<?php

it('mencegah menghapus user yang memiliki role admin (FR-M3)', function () {
    $admin = userWithRole('admin');
    $adminLain = userWithRole('admin', ['email' => 'admin2@test.local']);

    expect($admin->can('delete', $adminLain))->toBeFalse();
});

it('mengizinkan admin menghapus user non-admin', function () {
    $admin = userWithRole('admin');
    $timKerja = userWithRole('tim_kerja', ['email' => 'tim@test.local']);

    expect($admin->can('delete', $timKerja))->toBeTrue();
});

it('memblokir non-admin menghapus user apa pun', function () {
    $timKerja = userWithRole('tim_kerja');
    $validator = userWithRole('validator', ['email' => 'validator@test.local']);

    expect($timKerja->can('delete', $validator))->toBeFalse();
});