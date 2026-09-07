<?php

use App\Models\JumlahPts;

it('admin dapat menambah jumlah mahasiswa untuk suatu tahun anggaran', function () {
    $admin = userWithRole('admin');
    $tahun = makeTahunAnggaran();

    $response = $this->actingAs($admin)->post(route('admin.tools.jumlah-mahasiswa.store'), [
        'tahun_anggaran_id' => $tahun->id,
        'jumlah' => 1200,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('jumlah_mahasiswa', ['tahun_anggaran_id' => $tahun->id, 'jumlah' => 1200]);
});

it('menolak jumlah mahasiswa negatif', function () {
    $admin = userWithRole('admin');
    $tahun = makeTahunAnggaran();

    $response = $this->actingAs($admin)->post(route('admin.tools.jumlah-mahasiswa.store'), [
        'tahun_anggaran_id' => $tahun->id,
        'jumlah' => -5,
    ]);

    $response->assertSessionHasErrors('jumlah');
});

it('memblokir role selain admin mengakses Tools Jumlah Mahasiswa', function () {
    $timKerja = userWithRole('tim_kerja');

    $response = $this->actingAs($timKerja)->get(route('admin.tools.jumlah-mahasiswa.index'));

    $response->assertForbidden();
});

it('admin dapat menambah dan menghapus jumlah PTS', function () {
    $admin = userWithRole('admin');
    $tahun = makeTahunAnggaran();

    $this->actingAs($admin)->post(route('admin.tools.jumlah-pts.store'), [
        'tahun_anggaran_id' => $tahun->id,
        'jumlah' => 30,
    ]);

    $record = JumlahPts::firstWhere('tahun_anggaran_id', $tahun->id);

    $this->actingAs($admin)->delete(route('admin.tools.jumlah-pts.destroy', $record->id));

    $this->assertDatabaseMissing('jumlah_pts', ['id' => $record->id]);
});