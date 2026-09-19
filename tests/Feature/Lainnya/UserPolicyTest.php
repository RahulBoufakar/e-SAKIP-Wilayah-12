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

// --- AUDIT § B2: admin hanya boleh ubah akun admin miliknya sendiri ---

it('memblokir admin mengubah akun admin lain, tapi mengizinkan mengubah akun sendiri', function () {
    $admin = userWithRole('admin');
    $adminLain = userWithRole('admin', ['email' => 'admin2@test.local']);

    expect($admin->can('update', $adminLain))->toBeFalse()
        ->and($admin->can('update', $admin))->toBeTrue();
});

it('mengizinkan admin tetap mengubah user non-admin seperti biasa', function () {
    $admin = userWithRole('admin');
    $timKerja = userWithRole('tim_kerja', ['email' => 'tim-b2@test.local']);
    $validator = userWithRole('validator', ['email' => 'validator-b2@test.local']);

    expect($admin->can('update', $timKerja))->toBeTrue()
        ->and($admin->can('update', $validator))->toBeTrue();
});

it('super_admin tetap bisa mengubah akun admin lain lewat Gate::before', function () {
    $superAdmin = userWithRole('super_admin');
    $adminLain = userWithRole('admin', ['email' => 'admin3@test.local']);

    expect($superAdmin->can('update', $adminLain))->toBeTrue();
});

it('memblokir HTTP request admin yang mencoba mengubah akun admin lain', function () {
    $admin = userWithRole('admin');
    $adminLain = userWithRole('admin', ['email' => 'admin4@test.local']);

    $response = $this->actingAs($admin)->put(route('admin.master-data.user.update', $adminLain->id), [
        'name' => 'Dipaksa Diubah',
        'email' => $adminLain->email,
        'password' => '',
        'role' => 'admin',
    ]);

    $response->assertForbidden();
});
