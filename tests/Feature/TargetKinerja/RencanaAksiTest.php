<?php

use App\Models\RencanaAksi;
use App\Models\Triwulan;

it('admin dapat menyimpan rencana aksi tanpa gate Triwulan Aktif (Rule R-4)', function () {
    $admin = userWithRole('admin');
    $tahun = makeTahunAnggaran();
    $sasaran = makeSasaranKegiatan($tahun);
    $iku = makeIku($sasaran);

    // Sengaja tidak mengaktifkan triwulan apa pun.
    $tw1 = Triwulan::where('kode', 'TW1')->first();
    $tw2 = Triwulan::where('kode', 'TW2')->first();

    $response = $this->actingAs($admin)->put(route('admin.rencana-aksi.update', $iku), [
        'uraian' => [
            $tw1->id => 'Uraian triwulan 1',
            $tw2->id => 'Uraian triwulan 2',
        ],
    ]);

    $response->assertRedirect();
    expect(RencanaAksi::where('iku_id', $iku->id)->where('triwulan_id', $tw1->id)->value('uraian'))
        ->toBe('Uraian triwulan 1');
});

it('membuat baris rencana aksi untuk seluruh 4 triwulan meskipun uraian dikosongkan', function () {
    $admin = userWithRole('admin');
    $tahun = makeTahunAnggaran();
    $sasaran = makeSasaranKegiatan($tahun);
    $iku = makeIku($sasaran);

    $this->actingAs($admin)->put(route('admin.rencana-aksi.update', $iku), ['uraian' => []]);

    expect(RencanaAksi::where('iku_id', $iku->id)->count())->toBe(4);
});

it('menolak role selain admin untuk mengubah rencana aksi', function () {
    $timKerja = userWithRole('tim_kerja');
    $tahun = makeTahunAnggaran();
    $sasaran = makeSasaranKegiatan($tahun);
    $iku = makeIku($sasaran);
    $tw1 = Triwulan::where('kode', 'TW1')->first();

    $response = $this->actingAs($timKerja)->put(route('admin.rencana-aksi.update', $iku), [
        'uraian' => [$tw1->id => 'Coba ubah'],
    ]);

    $response->assertForbidden();
});