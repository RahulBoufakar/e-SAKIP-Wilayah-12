<?php

use App\Models\Triwulan;

it('admin dapat mengatur target capaian kinerja untuk triwulan mana pun, tidak dibatasi triwulan aktif', function () {
    $admin = userWithRole('admin');
    $tahun = makeTahunAnggaran();
    activateTriwulan($tahun, 'TW1');
    $sasaran = makeSasaranKegiatan($tahun);
    $iku = makeIku($sasaran);
    $tw2 = Triwulan::where('kode', 'TW2')->first();

    $response = $this->actingAs($admin)->put(route('admin.capaian-kinerja.target.update'), [
        'iku_id' => $iku->id,
        'triwulan_id' => $tw2->id,
        'tahun_anggaran_id' => $tahun->id,
        'target' => 75.5,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('capaian_kinerja', [
        'iku_id' => $iku->id,
        'triwulan_id' => $tw2->id,
        'target' => 75.5,
    ]);
});