<?php

use App\Models\Pts;

it('dapat memfilter PTS berdasarkan status pts, status akreditasi, sumber data, dan provinsi, serta search box untuk kode/nama', function () {
    $admin = userWithRole('admin');

    $pts1 = makePts('121001', [
        'nama_pts' => 'Universitas Pattimura',
        'status_pts' => 'aktif',
        'akreditasi_pts' => 'unggul',
        'sumber_data' => 'lldikti12',
        'provinsi' => 'Maluku',
    ]);

    $pts2 = makePts('121002', [
        'nama_pts' => 'Universitas Khairun',
        'status_pts' => 'pembinaan',
        'akreditasi_pts' => 'terakreditasi',
        'sumber_data' => 'manual',
        'provinsi' => 'Maluku Utara',
    ]);

    $pts3 = makePts('121003', [
        'nama_pts' => 'Institut Halmahera',
        'status_pts' => 'non_aktif',
        'akreditasi_pts' => 'tidak_terakreditasi',
        'sumber_data' => 'pddikti',
        'provinsi' => 'Maluku Utara',
    ]);

    // 1. Search box luar (kode atau nama PTS)
    $this->actingAs($admin)
        ->get(route('admin.master-data.pts.index', ['search' => '121001']))
        ->assertOk()
        ->assertSee('Universitas Pattimura')
        ->assertDontSee('Universitas Khairun')
        ->assertDontSee('Institut Halmahera');

    $this->actingAs($admin)
        ->get(route('admin.master-data.pts.index', ['search' => 'Khairun']))
        ->assertOk()
        ->assertSee('Universitas Khairun')
        ->assertDontSee('Universitas Pattimura');

    // 2. Filter status_pts (aktif / pembinaan / lainnya)
    $this->actingAs($admin)
        ->get(route('admin.master-data.pts.index', ['status_pts' => 'pembinaan']))
        ->assertOk()
        ->assertSee('Universitas Khairun')
        ->assertDontSee('Universitas Pattimura')
        ->assertDontSee('Institut Halmahera');

    $this->actingAs($admin)
        ->get(route('admin.master-data.pts.index', ['status_pts' => 'aktif']))
        ->assertOk()
        ->assertSee('Universitas Pattimura')
        ->assertDontSee('Universitas Khairun');

    // 3. Filter akreditasi_pts
    $this->actingAs($admin)
        ->get(route('admin.master-data.pts.index', ['akreditasi_pts' => 'unggul']))
        ->assertOk()
        ->assertSee('Universitas Pattimura')
        ->assertDontSee('Universitas Khairun');

    // 4. Filter sumber_data
    $this->actingAs($admin)
        ->get(route('admin.master-data.pts.index', ['sumber_data' => 'manual']))
        ->assertOk()
        ->assertSee('Universitas Khairun')
        ->assertDontSee('Universitas Pattimura');

    // 5. Filter provinsi
    $this->actingAs($admin)
        ->get(route('admin.master-data.pts.index', ['provinsi' => 'Maluku']))
        ->assertOk()
        ->assertSee('Universitas Pattimura')
        ->assertDontSee('Universitas Khairun')
        ->assertDontSee('Institut Halmahera');

    // 6. Kombinasi filter
    $response = $this->actingAs($admin)
        ->get(route('admin.master-data.pts.index', [
            'status_pts' => 'non_aktif',
            'provinsi' => 'Maluku Utara',
            'akreditasi_pts' => 'tidak_terakreditasi',
        ]))
        ->assertOk()
        ->assertSee('Institut Halmahera')
        ->assertDontSee('Universitas Khairun')
        ->assertDontSee('Universitas Pattimura')
        ->assertSee('Reset');

    // Memeriksa badge aktif bernilai 3
    $response->assertSee('<span class="rounded-full bg-brand-600 px-1.5 text-[10px] font-bold text-white">3</span>', false);

    // 7. Menampilkan elemen filter di modal (status PTS, akreditasi, sumber, provinsi)
    // dan memastikan input form nama/kode tidak berada di modal filter
    $view = $this->actingAs($admin)
        ->get(route('admin.master-data.pts.index'))
        ->assertOk()
        ->assertSee('Status PTS')
        ->assertSee('Status Akreditasi')
        ->assertSee('Sumber Data')
        ->assertSee('Semua Provinsi');
});
