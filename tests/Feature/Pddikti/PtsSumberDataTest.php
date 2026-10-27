<?php

use App\Models\Pts;

it('hanya admin yang boleh sync', function () {
    expect(userWithRole('admin')->can('sync', Pts::class))->toBeTrue()
        ->and(userWithRole('tim_kerja')->can('sync', Pts::class))->toBeFalse();
});

it('delete ditolak untuk baris bersumber API dan diizinkan untuk baris manual', function () {
    $admin = userWithRole('admin');

    expect($admin->can('delete', makePts('L1', ['sumber_data' => 'lldikti12'])))->toBeFalse()
        ->and($admin->can('delete', makePts('L2')))->toBeTrue();
});

it('request DELETE langsung ke baris API ditolak di sisi server', function () {
    $pts = makePts('L3', ['sumber_data' => 'lldikti12']);

    $this->actingAs(userWithRole('admin'))
        ->delete(route('admin.master-data.pts.destroy', $pts))
        ->assertForbidden();

    $this->assertDatabaseHas('pts', ['id' => $pts->id]);
});

it('edit Admin pada baris API mengubah sumber_data menjadi manual', function () {
    $pts = makePts('M1', ['sumber_data' => 'lldikti12']);

    $this->actingAs(userWithRole('admin'))
        ->put(route('admin.master-data.pts.update', $pts), [
            'kode_pts' => 'M1', 'nama_pts' => 'Nama Baru', 'provinsi' => 'Maluku', 'status_pts' => 'aktif',
        ])
        ->assertRedirect();

    expect($pts->fresh()->sumber_data)->toBe('manual')
        ->and($pts->fresh()->nama_pts)->toBe('Nama Baru');
});

it('halaman Data PTS tidak error untuk status null dan menampilkan badge sumber', function () {
    makePts('N1', ['status_pts' => null]);
    makePts('N2', ['sumber_data' => 'lldikti12']);

    $this->actingAs(userWithRole('admin'))
        ->get(route('admin.master-data.pts.index'))
        ->assertOk()
        ->assertSee('PTS N1')
        ->assertSee('Belum ada')
        ->assertSee('LLDikti12')
        ->assertSee('Sumber: LLDIKTI Wilayah XII');
});