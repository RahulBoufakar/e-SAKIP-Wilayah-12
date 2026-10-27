<?php

return [

    // Driver aktif. Ganti LLDikti 12 -> PDDikti langsung cukup dengan mengubah nilai ini di .env.
    'driver' => env('PDDIKTI_DRIVER', 'lldikti12'),

    'drivers' => [
        'lldikti12' => [
            'base_url' => env('LLDIKTI12_BASE_URL', 'https://app.lldikti12.id/api/v1'),
            'api_key' => env('LLDIKTI12_API_KEY'),
            'endpoints' => ['pts' => '/pts', 'akreditasi' => '/akreditasi/pts'],
            'per_page' => 100,
            'timeout' => 30, // detik
            'retry' => ['percobaan' => 3, 'jeda_detik' => 2], // jeda bertambah: 2, 4, 6 detik
            'maks_tunggu_429' => 60, // total detik menunggu Retry-After sebelum menyerah
        ],
        'pddikti' => [
            'base_url' => env('PDDIKTI_BASE_URL'),
            'api_key' => env('PDDIKTI_API_KEY'),
            'endpoints' => ['pts' => null, 'akreditasi' => null], // diisi saat akses PDDikti tersedia
            'per_page' => 100,
            'timeout' => 30,
            'retry' => ['percobaan' => 3, 'jeda_detik' => 2],
            'maks_tunggu_429' => 60,
        ],
    ],

    // Dipakai PtsSyncService (Tahap 3): dicocokkan persis, tanpa beda huruf besar/kecil & spasi.
    'provinsi' => ['Maluku', 'Maluku Utara'],

    // Nilai yang dikirim sebagai parameter status_pts ke API.
    'status_diambil' => ['Aktif', 'Non Aktif'],

    // Nilai yang dikirim sebagai parameter status_sk_diambil ke API.
    'status_sk_diambil' => 'Aktif',

    // Kunci dibandingkan case-insensitive oleh service. Nilai di luar daftar -> null + peringatan.
    'pemetaan' => [
        'status_pts' => [
            'aktif' => 'aktif',
            'non aktif' => 'non_aktif',
            'merger/penyatuan' => 'alih_bentuk',
            'pembinaan' => 'pembinaan',
            'tutup' => 'tutup',
        ],
        'akreditasi' => [
            'unggul' => 'unggul',
            'terakreditasi' => 'terakreditasi',
            'baik sekali' => 'terakreditasi',
            'baik' => 'terakreditasi',
            'a' => 'terakreditasi',
            'b' => 'terakreditasi',
            'c' => 'terakreditasi',
            'belum terakreditasi' => 'tidak_terakreditasi',
            'rencana penyatuan' => 'tidak_terakreditasi',
        ],
    ],

    // Dipakai SinkronisasiPtsJob (Tahap 3).
    'queue' => [
        'koneksi' => env('PDDIKTI_QUEUE_CONNECTION', 'database'),
        'antrean' => 'sinkronisasi',
    ],
];