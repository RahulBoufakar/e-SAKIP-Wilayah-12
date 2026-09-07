<?php

use App\Models\Pts;

it('memblokir hapus tim kerja yang masih terhubung ke IKU (D-6)', function () {
    $admin = userWithRole('admin');
    $tahun = makeTahunAnggaran();
    $sasaran = makeSasaranKegiatan($tahun);
    $iku = makeIku($sasaran);
    $tim = makeTimKerja();
    $iku->timKerja()->attach($tim->id);

    $response = $this->actingAs($admin)->delete(route('admin.master-data.tim-kerja.destroy', $tim->id));

    $response->assertRedirect();
    $this->assertDatabaseHas('tim_kerja', ['id' => $tim->id]);
});

it('menghapus tim kerja yang tidak terhubung ke IKU atau user', function () {
    $admin = userWithRole('admin');
    $tim = makeTimKerja();

    $this->actingAs($admin)->delete(route('admin.master-data.tim-kerja.destroy', $tim->id));

    $this->assertDatabaseMissing('tim_kerja', ['id' => $tim->id]);
});

it('memblokir hapus PTS yang masih ditagging pada program kerja', function () {
    $admin = userWithRole('admin');
    $pts = Pts::create(['kode_pts' => 'PTS001', 'nama_pts' => 'Universitas Uji', 'status_pts' => 'aktif']);

    $tahun = makeTahunAnggaran();
    $sasaran = makeSasaranKegiatan($tahun);
    $iku = makeIku($sasaran);
    $usulan = makeUsulan($iku);
    $usulan->pts()->attach($pts->id);

    $response = $this->actingAs($admin)->delete(route('admin.master-data.pts.destroy', $pts->id));

    $response->assertRedirect();
    $this->assertDatabaseHas('pts', ['id' => $pts->id]);
});