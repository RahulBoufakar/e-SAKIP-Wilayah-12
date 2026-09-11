<?php

it('memblokir role selain pimpinan mengakses Dashboard Eksekutif', function () {
    $timKerja = userWithRole('tim_kerja');

    $response = $this->actingAs($timKerja)->get(route('pimpinan.dashboard'));

    $response->assertForbidden();
});

it('memblokir tamu (belum login) mengakses Dashboard Eksekutif', function () {
    $response = $this->get(route('pimpinan.dashboard'));

    $response->assertRedirect(route('login'));
});

it('mengizinkan role pimpinan mengakses seluruh halaman modulnya', function () {
    $pimpinan = userWithRole('pimpinan');
    $tahun = makeTahunAnggaran();
    activateTriwulan($tahun, 'TW1');

    $routes = [
        route('pimpinan.dashboard'),
        route('pimpinan.iku-lldikti.index'),
        route('pimpinan.rencana-aksi.index'),
        route('pimpinan.kalender-proker.index', ['tahun' => 'berjalan']),
        route('pimpinan.laporan.index'),
    ];

    foreach ($routes as $url) {
        $this->actingAs($pimpinan)->get($url)->assertOk();
    }
});

it('menampilkan halaman data-belum-tersedia saat belum ada Tahun Anggaran sama sekali', function () {
    $pimpinan = userWithRole('pimpinan');

    // Sengaja tidak memanggil makeTahunAnggaran() — DB kosong.
    $response = $this->actingAs($pimpinan)->get(route('pimpinan.dashboard'));

    $response->assertOk()->assertSee('Data Belum Tersedia');
});

it('admin tetap bisa mengakses jalur cadangan Laporan Pimpinan di Tools', function () {
    $admin = userWithRole('admin');
    makeTahunAnggaran();

    $this->actingAs($admin)->get(route('admin.tools.laporan-pimpinan.index'))->assertOk();
});

it('memblokir role pimpinan mengakses jalur Tools milik Admin (dipisah per-namespace, bukan lewat policy)', function () {
    $pimpinan = userWithRole('pimpinan');

    $response = $this->actingAs($pimpinan)->get(route('admin.tools.laporan-pimpinan.index'));

    $response->assertForbidden();
});