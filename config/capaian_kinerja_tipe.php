<?php

/**
 * Spek Capaian Kinerja Hybrid §3 & §4.3 — definisi TAMPILAN (label, kolom
 * form/tabel) per tipe_iku.
 *
 * PENTING: file ini murni konfigurasi presentasi Blade (dipakai show/index
 * view Tim Kerja & Validator untuk merender kolom berbeda tanpa menduplikasi
 * Blade 9x). Ini BUKAN registry kalkulasi — formula/kalkulasi tetap ditulis
 * eksplisit per tipe di App\Services\CapaianKinerjaHitungService via match(),
 * sesuai keputusan §2 spek (draf awal ber-abstraksi CapaianTipeInterface +
 * Registry generik SUDAH DITOLAK dan sengaja tidak dipakai lagi).
 *
 * 'arsitektur_pts' sengaja tidak punya 'kolom' di sini — strukturnya (2 tabel
 * berdampingan) ditangani langsung di Blade
 * tipe/arsitektur-pts.blade.php, bukan lewat renderer generik kolom tunggal.
 */

return [

    'kepuasan_layanan' => [
        'label' => 'Keunggulan Layanan LLDIKTI (Kepuasan Layanan)',
        'satuan' => '%',
        'kolom' => [
            ['field' => 'total_responden', 'label' => 'Total Responden', 'tipe' => 'number', 'required' => true],
            ['field' => 'hasil_perhitungan_kepuasan', 'label' => 'Hasil Perhitungan Kepuasan (%)', 'tipe' => 'number_decimal', 'required' => true, 'rule' => 'max:100'],
        ],
        'entri_tunggal' => true,
    ],

    'arsitektur_pts' => [
        'label' => 'Arsitektur PTS (Akreditasi + Penggabungan)',
        'satuan' => '%',
    ],

    'tata_kelola' => [
        'label' => 'Tata Kelola LLDIKTI (SAKIP + ZI)',
        'satuan' => 'Nilai',
        'entri_tunggal' => true,
        'kolom' => [
            ['field' => 'predikat_sakip', 'label' => 'Predikat SAKIP', 'tipe' => 'select', 'opsi' => \App\Support\SkorSakipZi::opsiSakip(), 'required' => true, 'tampil' => 'predikat_sakip_label'],
            ['field' => 'predikat_zi', 'label' => 'Predikat ZI', 'tipe' => 'select', 'opsi' => \App\Support\SkorSakipZi::opsiZi(), 'required' => true, 'tampil' => 'predikat_zi_label'],
        ],
    ],

    'fasilitasi_mutu_pts' => [
        'label' => 'Fasilitasi Peningkatan Mutu PTS',
        'satuan' => '%',
        'butuh_pts' => true,
        'bukti_wajib' => true,
        'kolom' => [
            ['field' => 'bentuk_fasilitasi', 'label' => 'Bentuk Fasilitasi', 'tipe' => 'text', 'required' => true],
            ['field' => 'tanggal_kegiatan', 'label' => 'Tanggal Kegiatan', 'tipe' => 'date', 'required' => true],
        ],
    ],

    'kebijakan_ppks' => [
        'label' => 'Pencegahan & Penanganan Kekerasan/Narkoba/Korupsi',
        'satuan' => '%',
        'butuh_pts' => true,
        'kolom' => [
            ['field' => 'file_implementasi_ppks_antinarkoba_antikorupsi', 'label' => 'Implementasi PPKS / Anti Narkoba / Anti Korupsi', 'tipe' => 'file', 'required' => true, 'link_teks' => 'implementasi'],
        ],
    ],

    'fasilitasi_kemahasiswaan' => [
        'label' => 'Fasilitasi Pengembangan Kemahasiswaan',
        'satuan' => '%',
        'butuh_pts' => true,
        'bukti_wajib' => true,
        'kolom' => [
            ['field' => 'bentuk_fasilitasi', 'label' => 'Bentuk Fasilitasi', 'tipe' => 'text', 'required' => true],
            ['field' => 'tanggal_kegiatan', 'label' => 'Tanggal Kegiatan', 'tipe' => 'date', 'required' => true],
        ],
    ],

    'dosen_naik_jafung' => [
        'label' => 'Jumlah Dosen PTS Naik Jabatan Fungsional',
        'satuan' => 'Orang',
        'butuh_pts' => true,
        'bukti_wajib' => true,
        'kolom' => [
            ['field' => 'nama_dosen', 'label' => 'Nama Dosen', 'tipe' => 'text', 'required' => true],
            ['field' => 'nidn', 'label' => 'NIDN/NUPTK', 'tipe' => 'text', 'required' => true, 'rule' => 'max:16'],
            ['field' => 'tipe_kepegawaian', 'label' => 'Tipe Kepegawaian', 'tipe' => 'select', 'opsi' => ['Dosen Tetap Yayasan', 'Dosen PNS'], 'required' => true],
            ['field' => 'jenjang_asal', 'label' => 'Jenjang Asal', 'tipe' => 'select', 'opsi' => ['tenaga_pengajar', 'asisten_ahli', 'lektor', 'lektor_kepala'], 'required' => true],
            ['field' => 'jenjang_baru', 'label' => 'Jenjang Baru', 'tipe' => 'select', 'opsi' => ['asisten_ahli', 'lektor', 'lektor_kepala', 'profesor'], 'required' => true],
            ['field' => 'no_sk', 'label' => 'No. SK', 'tipe' => 'text', 'required' => true],
            ['field' => 'tanggal_sk', 'label' => 'Tanggal SK', 'tipe' => 'date', 'required' => true],
        ],
    ],

    'fasilitasi_penelitian' => [
        'label' => 'Fasilitasi Penelitian/Publikasi/PkM/Kemitraan PTS',
        'satuan' => '%',
        'butuh_pts' => true,
        'kolom' => [
            ['field' => 'bentuk_fasilitasi', 'label' => 'Bentuk Fasilitasi', 'tipe' => 'text', 'required' => true],
            ['field' => 'tanggal_kegiatan', 'label' => 'Tanggal Kegiatan', 'tipe' => 'date', 'required' => true],
        ],
    ],

    'nilai_rka' => [
        'label' => 'Nilai Kinerja Anggaran atas Pelaksanaan RKA-K/L',
        'satuan' => 'Nilai',
        'kolom' => [
            ['field' => 'nilai_rka', 'label' => 'Nilai RKA-K/L', 'tipe' => 'number_decimal', 'required' => true],
        ],
        'entri_tunggal' => true,
    ],

];
