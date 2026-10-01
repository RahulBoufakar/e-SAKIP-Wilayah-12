<?php

it('memfilter Usulan Proker Tim Kerja berdasarkan IKU', function () {
    $tahun = makeTahunAnggaran();
    $tim = makeTimKerja();
    $sasaran = makeSasaranKegiatan($tahun);
    $iku1 = makeIku($sasaran);
    $iku2 = makeIku($sasaran, ['deskripsi' => 'IKU Dua']);
    $iku1->timKerja()->attach($tim->id);
    $iku2->timKerja()->attach($tim->id);
    makeUsulan($iku1, ['nama_usulan' => 'Usulan A']);
    makeUsulan($iku2, ['nama_usulan' => 'Usulan B']);

    $user = userWithRole('tim_kerja');
    $user->timKerja()->attach($tim->id);

    $this->actingAs($user)
        ->get(route('tim-kerja.usulan-program-kerja.index', ['iku_id' => $iku1->id]))
        ->assertOk()->assertSee('Usulan A')->assertDontSee('Usulan B');
});