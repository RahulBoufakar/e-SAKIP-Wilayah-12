<?php

use App\Models\DetailKegiatan;

it('hanya menampilkan proker berstatus approved pada Kalender Proker Pimpinan', function () {
    $pimpinan = userWithRole('pimpinan');
    $tahun = makeTahunAnggaran();
    $sasaran = makeSasaranKegiatan($tahun);
    $iku = makeIku($sasaran);

    $approved = makeUsulan($iku, ['nama_usulan' => 'Proker Disetujui', 'status_validasi' => 'approved']);
    DetailKegiatan::create([
        'usulan_program_kerja_id' => $approved->id,
        'nama_detail' => 'Detail Disetujui',
        'tempat_pelaksanaan' => 'Kantor',
        'bentuk_kegiatan' => 'Luring',
        'bulan_kegiatan' => [3],
        'anggaran' => 1000000,
    ]);

    $menunggu = makeUsulan($iku, ['nama_usulan' => 'Proker Menunggu Validasi', 'status_validasi' => 'menunggu_validasi']);
    DetailKegiatan::create([
        'usulan_program_kerja_id' => $menunggu->id,
        'nama_detail' => 'Detail Menunggu',
        'tempat_pelaksanaan' => 'Kantor',
        'bentuk_kegiatan' => 'Daring',
        'bulan_kegiatan' => [3],
        'anggaran' => 500000,
    ]);

    $response = $this->actingAs($pimpinan)->get(route('pimpinan.kalender-proker.index', ['tahun' => 'berjalan']));

    $response->assertOk()
        ->assertSee('Proker Disetujui')
        ->assertDontSee('Proker Menunggu Validasi');
});