<?php

use App\Models\JumlahPts;
use App\Models\Pts;
use Spatie\Activitylog\Models\Activity;

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

it('menghapus PTS tanpa 404, data hilang, dan tercatat di audit log', function () {
    $admin = userWithRole('admin');
    $pts = Pts::create(['kode_pts' => 'PTS100', 'nama_pts' => 'Uji', 'status_pts' => 'aktif']);

    $response = $this->actingAs($admin)
        ->from(route('admin.master-data.pts.index'))
        ->delete(route('admin.master-data.pts.destroy', $pts->id));

    $response->assertRedirect(route('admin.master-data.pts.index')); // (a) bukan 404
    $this->assertDatabaseMissing('pts', ['id' => $pts->id]);          // (b)
    expect(Activity::where('log_name', 'audit_trail')                  // (c)
        ->where('description', 'like', '%menghapus data PTS%')->exists())->toBeTrue();
});