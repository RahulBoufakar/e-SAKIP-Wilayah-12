<?php

use App\Models\CapaianAkreditasiPts;
use App\Models\CapaianDosenNaikJafung;
use App\Models\CapaianFasilitasiKemahasiswaan;
use App\Models\CapaianFasilitasiMutuPts;
use App\Models\CapaianFasilitasiPenelitian;
use App\Models\CapaianKebijakanPpks;
use App\Models\CapaianKepuasanLayanan;
use App\Models\CapaianNilaiRka;
use App\Models\CapaianPenggabunganPts;
use App\Models\CapaianTataKelola;
use App\Models\JumlahPts;
use App\Models\JumlahPublikasi;
use App\Models\Pts;
use App\Services\CapaianKinerjaHitungService;

/** Buat header CapaianKinerja untuk tipe_iku tertentu, IKU & Tahun Anggaran baru. */
function buatCapaianUntukTipe(string $tipeIku): \App\Models\CapaianKinerja
{
    $tahun = makeTahunAnggaran();
    $sasaran = makeSasaranKegiatan($tahun);
    $iku = makeIku($sasaran, ['tipe_iku' => $tipeIku]);

    return makeCapaianKinerja($iku, $tahun);
}

beforeEach(function () {
    $this->hitung = app(CapaianKinerjaHitungService::class);
});

// --- IKU 1: kepuasan_layanan (entri tunggal sejak migrasi perketat) ---

it('kepuasan_layanan = responden_puas / total_responden x 100%, baris disetujui', function () {
    $capaian = buatCapaianUntukTipe('kepuasan_layanan');

    CapaianKepuasanLayanan::create(['capaian_kinerja_id' => $capaian->id, 'total_responden' => 100, 'responden_puas' => 85, 'status_validasi' => 'disetujui']);

    expect($this->hitung->hitung($capaian->fresh()))->toBe(85.0);
});

it('kepuasan_layanan null saat belum ada baris disetujui', function () {
    $capaian = buatCapaianUntukTipe('kepuasan_layanan');

    expect($this->hitung->hitung($capaian))->toBeNull();
});

it('kepuasan_layanan abaikan baris menunggu_validasi', function () {
    $capaian = buatCapaianUntukTipe('kepuasan_layanan');

    CapaianKepuasanLayanan::create(['capaian_kinerja_id' => $capaian->id, 'total_responden' => 100, 'responden_puas' => 85, 'status_validasi' => 'menunggu_validasi']);

    expect($this->hitung->hitung($capaian->fresh()))->toBeNull();
});

// --- IKU 2: arsitektur_pts (gabungan 2 tabel) ---

it('arsitektur_pts = (COUNT akreditasi + COUNT penggabungan) / jumlah_pts x 100%', function () {
    $capaian = buatCapaianUntukTipe('arsitektur_pts');
    JumlahPts::create(['tahun_anggaran_id' => $capaian->tahun_anggaran_id, 'jumlah' => 10]);
    $pts1 = Pts::create(['kode_pts' => 'PTS-A1', 'nama_pts' => 'A1', 'status_pts' => 'aktif']);
    $pts2 = Pts::create(['kode_pts' => 'PTS-A2', 'nama_pts' => 'A2', 'status_pts' => 'aktif']);

    CapaianAkreditasiPts::create(['capaian_kinerja_id' => $capaian->id, 'pts_id' => $pts1->id, 'akreditasi' => 'Unggul', 'no_sk' => 'SK1', 'masa_berlaku' => now()->addYear(), 'status_validasi' => 'disetujui']);
    CapaianPenggabunganPts::create(['capaian_kinerja_id' => $capaian->id, 'pts_id' => $pts2->id, 'sk_penggabungan' => 'SK2', 'status_validasi' => 'disetujui']);

    expect($this->hitung->hitung($capaian))->toBe(20.0);
});

// --- IKU 3: tata_kelola (mapping kategorikal) ---

it('tata_kelola dihitung dari baris disetujui terbaru via SkorSakipZi', function () {
    $capaian = buatCapaianUntukTipe('tata_kelola');

    CapaianTataKelola::create(['capaian_kinerja_id' => $capaian->id, 'predikat_sakip' => 'BB', 'predikat_zi' => 'WBK', 'status_validasi' => 'disetujui']);

    expect($this->hitung->hitung($capaian))->toBe(80.0); // (70+90)/2
});

// --- IKU 4: fasilitasi_mutu_pts ---

it('fasilitasi_mutu_pts = COUNT DISTINCT pts / jumlah_pts x 100%, dedup PTS yang difasilitasi >1x', function () {
    $capaian = buatCapaianUntukTipe('fasilitasi_mutu_pts');
    JumlahPts::create(['tahun_anggaran_id' => $capaian->tahun_anggaran_id, 'jumlah' => 4]);
    $pts = Pts::create(['kode_pts' => 'PTS-B1', 'nama_pts' => 'B1', 'status_pts' => 'aktif']);

    CapaianFasilitasiMutuPts::create(['capaian_kinerja_id' => $capaian->id, 'pts_id' => $pts->id, 'bentuk_fasilitasi' => 'Pelatihan A', 'tanggal_kegiatan' => now(), 'file_bukti_dukung' => 'bukti1.pdf', 'status_validasi' => 'disetujui']);
    CapaianFasilitasiMutuPts::create(['capaian_kinerja_id' => $capaian->id, 'pts_id' => $pts->id, 'bentuk_fasilitasi' => 'Pelatihan B', 'tanggal_kegiatan' => now(), 'file_bukti_dukung' => 'bukti2.pdf', 'status_validasi' => 'disetujui']);

    expect($this->hitung->hitung($capaian))->toBe(25.0); // 1 (dedup) / 4
});

// --- IKU 5: kebijakan_ppks (syarat 3 kolom terisi) ---

it('kebijakan_ppks hanya menghitung PTS yang ketiga kolom implementasinya terisi', function () {
    $capaian = buatCapaianUntukTipe('kebijakan_ppks');
    JumlahPts::create(['tahun_anggaran_id' => $capaian->tahun_anggaran_id, 'jumlah' => 2]);
    $ptsLengkap = Pts::create(['kode_pts' => 'PTS-C1', 'nama_pts' => 'C1', 'status_pts' => 'aktif']);
    $ptsTidakLengkap = Pts::create(['kode_pts' => 'PTS-C2', 'nama_pts' => 'C2', 'status_pts' => 'aktif']);

    CapaianKebijakanPpks::create([
        'capaian_kinerja_id' => $capaian->id, 'pts_id' => $ptsLengkap->id,
        'file_implementasi_ppks' => 'ppks.pdf', 'file_implementasi_anti_narkoba' => 'narkoba.pdf', 'file_implementasi_anti_korupsi' => 'korupsi.pdf',
        'status_validasi' => 'disetujui',
    ]);
    CapaianKebijakanPpks::create([
        'capaian_kinerja_id' => $capaian->id, 'pts_id' => $ptsTidakLengkap->id,
        'file_implementasi_ppks' => 'ppks2.pdf', 'file_implementasi_anti_narkoba' => null, 'file_implementasi_anti_korupsi' => 'korupsi2.pdf',
        'status_validasi' => 'disetujui',
    ]);

    expect($this->hitung->hitung($capaian))->toBe(50.0); // hanya 1 dari 2 PTS lengkap
});

// --- IKU 6: fasilitasi_kemahasiswaan (pola sama IKU 4) ---

it('fasilitasi_kemahasiswaan = COUNT DISTINCT pts / jumlah_pts x 100%', function () {
    $capaian = buatCapaianUntukTipe('fasilitasi_kemahasiswaan');
    JumlahPts::create(['tahun_anggaran_id' => $capaian->tahun_anggaran_id, 'jumlah' => 5]);
    $pts = Pts::create(['kode_pts' => 'PTS-D1', 'nama_pts' => 'D1', 'status_pts' => 'aktif']);

    CapaianFasilitasiKemahasiswaan::create(['capaian_kinerja_id' => $capaian->id, 'pts_id' => $pts->id, 'bentuk_fasilitasi' => 'Lomba', 'tanggal_kegiatan' => now(), 'file_bukti_dukung' => 'bukti.pdf', 'status_validasi' => 'disetujui']);

    expect($this->hitung->hitung($capaian))->toBe(20.0);
});

// --- IKU 7: dosen_naik_jafung (dedup NIDN, satuan Orang) ---

it('dosen_naik_jafung = COUNT baris disetujui (dedup NIDN oleh constraint DB), satuan Orang', function () {
    $capaian = buatCapaianUntukTipe('dosen_naik_jafung');
    $pts = Pts::create(['kode_pts' => 'PTS-E1', 'nama_pts' => 'E1', 'status_pts' => 'aktif']);

    CapaianDosenNaikJafung::create(['capaian_kinerja_id' => $capaian->id, 'pts_id' => $pts->id, 'nama_dosen' => 'Dosen A', 'nidn' => '001', 'jenjang_asal' => 'asisten_ahli', 'jenjang_baru' => 'lektor', 'no_sk' => 'SK1', 'tanggal_sk' => now(), 'file_bukti_dukung' => 'sk1.pdf', 'status_validasi' => 'disetujui']);
    CapaianDosenNaikJafung::create(['capaian_kinerja_id' => $capaian->id, 'pts_id' => $pts->id, 'nama_dosen' => 'Dosen B', 'nidn' => '002', 'jenjang_asal' => 'asisten_ahli', 'jenjang_baru' => 'lektor', 'no_sk' => 'SK2', 'tanggal_sk' => now(), 'file_bukti_dukung' => 'sk2.pdf', 'status_validasi' => 'disetujui']);
    CapaianDosenNaikJafung::create(['capaian_kinerja_id' => $capaian->id, 'pts_id' => $pts->id, 'nama_dosen' => 'Dosen C', 'nidn' => '003', 'jenjang_asal' => 'lektor', 'jenjang_baru' => 'lektor_kepala', 'no_sk' => 'SK3', 'tanggal_sk' => now(), 'file_bukti_dukung' => 'sk3.pdf', 'status_validasi' => 'menunggu_validasi']);

    expect($this->hitung->hitung($capaian))->toBe(2.0); // hanya 2 baris disetujui (NIDN 001 + 002), NIDN 003 masih menunggu
});

it('constraint DB mencegah duplikasi NIDN pada header yang sama', function () {
    $capaian = buatCapaianUntukTipe('dosen_naik_jafung');
    $pts = Pts::create(['kode_pts' => 'PTS-E2', 'nama_pts' => 'E2', 'status_pts' => 'aktif']);

    CapaianDosenNaikJafung::create(['capaian_kinerja_id' => $capaian->id, 'pts_id' => $pts->id, 'nama_dosen' => 'Dosen A', 'nidn' => '001', 'jenjang_asal' => 'asisten_ahli', 'jenjang_baru' => 'lektor', 'no_sk' => 'SK1', 'tanggal_sk' => now(), 'file_bukti_dukung' => 'sk1.pdf', 'status_validasi' => 'disetujui']);

    CapaianDosenNaikJafung::create(['capaian_kinerja_id' => $capaian->id, 'pts_id' => $pts->id, 'nama_dosen' => 'Dosen A', 'nidn' => '001', 'jenjang_asal' => 'lektor', 'jenjang_baru' => 'lektor_kepala', 'no_sk' => 'SK1B', 'tanggal_sk' => now(), 'file_bukti_dukung' => 'sk1b.pdf', 'status_validasi' => 'disetujui']);
})->throws(\Illuminate\Database\QueryException::class);

// --- IKU 8: fasilitasi_penelitian (denominator jumlah_publikasi, BUKAN jumlah_pts) ---

it('fasilitasi_penelitian memakai jumlah_publikasi sebagai denominator, bukan jumlah_pts', function () {
    $capaian = buatCapaianUntukTipe('fasilitasi_penelitian');
    JumlahPts::create(['tahun_anggaran_id' => $capaian->tahun_anggaran_id, 'jumlah' => 999]); // sengaja jauh beda, harus DIABAIKAN
    JumlahPublikasi::create(['tahun_anggaran_id' => $capaian->tahun_anggaran_id, 'jumlah' => 5]);
    $pts = Pts::create(['kode_pts' => 'PTS-F1', 'nama_pts' => 'F1', 'status_pts' => 'aktif']);

    CapaianFasilitasiPenelitian::create(['capaian_kinerja_id' => $capaian->id, 'pts_id' => $pts->id, 'dosen_perwakilan' => 'Dosen X', 'bentuk_fasilitasi' => 'Hibah', 'output' => 'Jurnal', 'file_bukti_dukung' => 'bukti.pdf', 'status_validasi' => 'disetujui']);

    expect($this->hitung->hitung($capaian))->toBe(20.0); // 1/5 x 100, bukan 1/999
});

// --- IKU 9: nilai_rka ---

it('nilai_rka mengambil baris disetujui terbaru tanpa pembagian', function () {
    $capaian = buatCapaianUntukTipe('nilai_rka');

    CapaianNilaiRka::create(['capaian_kinerja_id' => $capaian->id, 'nilai_rka' => 88.5, 'status_validasi' => 'disetujui']);

    expect($this->hitung->hitung($capaian))->toBe(88.5);
});

it('mengembalikan null untuk tipe_iku yang tidak dikenal (di luar 9 scope hybrid)', function () {
    $capaian = buatCapaianUntukTipe('tidak_ada_tipe_ini');

    expect($this->hitung->hitung($capaian))->toBeNull();
});
