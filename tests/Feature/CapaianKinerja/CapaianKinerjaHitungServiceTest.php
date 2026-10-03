<?php

use App\Models\CapaianAkreditasiPts;
use App\Models\CapaianDosenNaikJafung;
use App\Models\CapaianFasilitasiKemahasiswaan;
use App\Models\CapaianFasilitasiMutuPts;
use App\Models\CapaianFasilitasiPenelitian;
use App\Models\CapaianKebijakanPpks;
use App\Models\CapaianKepuasanLayanan;
use App\Models\CapaianKinerja;
use App\Models\CapaianNilaiRka;
use App\Models\CapaianPenggabunganPts;
use App\Models\CapaianTataKelola;
use App\Models\JumlahPts;
use App\Models\JumlahPublikasi;
use App\Models\Pts;
use App\Models\Triwulan;
use App\Services\CapaianKinerjaHitungService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;

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

// --- IKU 1: kepuasan_layanan (entri tunggal, nilai % diinput langsung) ---

it('skema capaian_kepuasan_layanan memakai hasil_perhitungan_kepuasan dan tidak lagi responden_puas', function () {
    expect(Schema::hasColumn('capaian_kepuasan_layanan', 'hasil_perhitungan_kepuasan'))->toBeTrue()
        ->and(Schema::hasColumn('capaian_kepuasan_layanan', 'responden_puas'))->toBeFalse();
});

it('kepuasan_layanan = hasil_perhitungan_kepuasan pada baris disetujui', function () {
    $capaian = buatCapaianUntukTipe('kepuasan_layanan');

    CapaianKepuasanLayanan::create(['capaian_kinerja_id' => $capaian->id, 'total_responden' => 100, 'hasil_perhitungan_kepuasan' => 85, 'status_validasi' => 'disetujui']);

    expect($this->hitung->hitung($capaian->fresh()))->toBe(85.0);
});

it('kepuasan_layanan null saat belum ada baris disetujui', function () {
    $capaian = buatCapaianUntukTipe('kepuasan_layanan');

    expect($this->hitung->hitung($capaian))->toBeNull();
});

it('kepuasan_layanan abaikan baris menunggu_validasi', function () {
    $capaian = buatCapaianUntukTipe('kepuasan_layanan');

    CapaianKepuasanLayanan::create(['capaian_kinerja_id' => $capaian->id, 'total_responden' => 100, 'hasil_perhitungan_kepuasan' => 85, 'status_validasi' => 'menunggu_validasi']);

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

// --- IKU 5 / 2.2: kebijakan_ppks (syarat 1 dokumen wajib terisi) ---

it('kebijakan_ppks hanya menghitung PTS yang dokumen implementasi wajibnya terisi', function () {
    $capaian = buatCapaianUntukTipe('kebijakan_ppks');
    JumlahPts::create(['tahun_anggaran_id' => $capaian->tahun_anggaran_id, 'jumlah' => 2]);
    $ptsLengkap = Pts::create(['kode_pts' => 'PTS-C1', 'nama_pts' => 'C1', 'status_pts' => 'aktif']);
    $ptsTidakLengkap = Pts::create(['kode_pts' => 'PTS-C2', 'nama_pts' => 'C2', 'status_pts' => 'aktif']);

    CapaianKebijakanPpks::create([
        'capaian_kinerja_id' => $capaian->id, 'pts_id' => $ptsLengkap->id,
        'file_implementasi_ppks_antinarkoba_antikorupsi' => 'implementasi.pdf',
        'status_validasi' => 'disetujui',
    ]);
    CapaianKebijakanPpks::create([
        'capaian_kinerja_id' => $capaian->id, 'pts_id' => $ptsTidakLengkap->id,
        'file_implementasi_ppks_antinarkoba_antikorupsi' => null,
        'status_validasi' => 'disetujui',
    ]);

    expect($this->hitung->hitung($capaian))->toBe(50.0); // hanya 1 dari 2 PTS punya dokumen wajib
});

it('kebijakan_ppks: file_bukti_dukung opsional, tidak menentukan PTS dihitung atau tidak', function () {
    $capaian = buatCapaianUntukTipe('kebijakan_ppks');
    JumlahPts::create(['tahun_anggaran_id' => $capaian->tahun_anggaran_id, 'jumlah' => 2]);
    $ptsTanpaBukti = Pts::create(['kode_pts' => 'PTS-C3', 'nama_pts' => 'C3', 'status_pts' => 'aktif']);
    $ptsHanyaBukti = Pts::create(['kode_pts' => 'PTS-C4', 'nama_pts' => 'C4', 'status_pts' => 'aktif']);

    CapaianKebijakanPpks::create(['capaian_kinerja_id' => $capaian->id, 'pts_id' => $ptsTanpaBukti->id, 'file_implementasi_ppks_antinarkoba_antikorupsi' => 'impl.pdf', 'file_bukti_dukung' => null, 'status_validasi' => 'disetujui']);
    CapaianKebijakanPpks::create(['capaian_kinerja_id' => $capaian->id, 'pts_id' => $ptsHanyaBukti->id, 'file_implementasi_ppks_antinarkoba_antikorupsi' => null, 'file_bukti_dukung' => 'bukti.pdf', 'status_validasi' => 'disetujui']);

    expect($this->hitung->hitung($capaian))->toBe(50.0);
});

// --- IKU 6: fasilitasi_kemahasiswaan (pola sama IKU 4) ---

it('fasilitasi_kemahasiswaan = COUNT DISTINCT pts / jumlah_pts x 100%', function () {
    $capaian = buatCapaianUntukTipe('fasilitasi_kemahasiswaan');
    JumlahPts::create(['tahun_anggaran_id' => $capaian->tahun_anggaran_id, 'jumlah' => 5]);
    $pts = Pts::create(['kode_pts' => 'PTS-D1', 'nama_pts' => 'D1', 'status_pts' => 'aktif']);

    CapaianFasilitasiKemahasiswaan::create(['capaian_kinerja_id' => $capaian->id, 'pts_id' => $pts->id, 'bentuk_fasilitasi' => 'Lomba', 'tanggal_kegiatan' => now(), 'file_bukti_dukung' => 'bukti.pdf', 'status_validasi' => 'disetujui']);

    expect($this->hitung->hitung($capaian))->toBe(20.0);
});

it('fasilitasi_kemahasiswaan menghitung satu PTS sekali walau punya banyak fasilitasi (distinct pts_id)', function () {
    $capaian = buatCapaianUntukTipe('fasilitasi_kemahasiswaan');
    JumlahPts::create(['tahun_anggaran_id' => $capaian->tahun_anggaran_id, 'jumlah' => 4]);
    $ptsA = Pts::create(['kode_pts' => 'PTS-D2', 'nama_pts' => 'D2', 'status_pts' => 'aktif']);
    $ptsB = Pts::create(['kode_pts' => 'PTS-D3', 'nama_pts' => 'D3', 'status_pts' => 'aktif']);

    CapaianFasilitasiKemahasiswaan::create(['capaian_kinerja_id' => $capaian->id, 'pts_id' => $ptsA->id, 'bentuk_fasilitasi' => 'Lomba', 'tanggal_kegiatan' => now(), 'file_bukti_dukung' => 'a1.pdf', 'status_validasi' => 'disetujui']);
    CapaianFasilitasiKemahasiswaan::create(['capaian_kinerja_id' => $capaian->id, 'pts_id' => $ptsA->id, 'bentuk_fasilitasi' => 'Seminar', 'tanggal_kegiatan' => now(), 'file_bukti_dukung' => 'a2.pdf', 'status_validasi' => 'disetujui']);
    CapaianFasilitasiKemahasiswaan::create(['capaian_kinerja_id' => $capaian->id, 'pts_id' => $ptsB->id, 'bentuk_fasilitasi' => 'Lomba', 'tanggal_kegiatan' => now(), 'file_bukti_dukung' => 'b1.pdf', 'status_validasi' => 'disetujui']);

    expect($this->hitung->hitung($capaian))->toBe(50.0); // 2 PTS / 4, bukan 3 / 4
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
// --- IKU 3.3: fasilitasi_penelitian (denominator jumlah_publikasi milik header, BUKAN jumlah_pts) ---

function barisPenelitian(int $capaianId, int $ptsId, string $bentuk, string $tanggal = '2026-03-10'): CapaianFasilitasiPenelitian
{
    return CapaianFasilitasiPenelitian::create([
        'capaian_kinerja_id' => $capaianId, 'pts_id' => $ptsId,
        'bentuk_fasilitasi' => $bentuk, 'tanggal_kegiatan' => $tanggal,
        'status_validasi' => 'disetujui',
    ]);
}

it('fasilitasi_penelitian memakai jumlah_publikasi milik header ini sebagai denominator, bukan jumlah_pts', function () {
    $capaian = buatCapaianUntukTipe('fasilitasi_penelitian');
    JumlahPts::create(['tahun_anggaran_id' => $capaian->tahun_anggaran_id, 'jumlah' => 999]); // harus DIABAIKAN
    JumlahPublikasi::create(['tahun_anggaran_id' => $capaian->tahun_anggaran_id, 'capaian_kinerja_id' => $capaian->id, 'jumlah' => 5]);
    $pts = Pts::create(['kode_pts' => 'PTS-F1', 'nama_pts' => 'F1', 'status_pts' => 'aktif']);

    barisPenelitian($capaian->id, $pts->id, 'Hibah');

    expect($this->hitung->hitung($capaian))->toBe(20.0); // 1/5 x 100
});

it('fasilitasi_penelitian menghitung satu PTS sekali walau punya banyak fasilitasi (distinct pts_id)', function () {
    $capaian = buatCapaianUntukTipe('fasilitasi_penelitian');
    JumlahPublikasi::create(['tahun_anggaran_id' => $capaian->tahun_anggaran_id, 'capaian_kinerja_id' => $capaian->id, 'jumlah' => 4]);
    $ptsA = Pts::create(['kode_pts' => 'PTS-F2', 'nama_pts' => 'F2', 'status_pts' => 'aktif']);
    $ptsB = Pts::create(['kode_pts' => 'PTS-F3', 'nama_pts' => 'F3', 'status_pts' => 'aktif']);

    barisPenelitian($capaian->id, $ptsA->id, 'Hibah');
    barisPenelitian($capaian->id, $ptsA->id, 'Workshop');
    barisPenelitian($capaian->id, $ptsB->id, 'Hibah');

    expect($this->hitung->hitung($capaian))->toBe(50.0); // 2 PTS / 4, bukan 3 / 4
});

it('fasilitasi_penelitian null bila jumlah publikasi belum diisi atau 0', function () {
    $capaian = buatCapaianUntukTipe('fasilitasi_penelitian');
    $pts = Pts::create(['kode_pts' => 'PTS-F4', 'nama_pts' => 'F4', 'status_pts' => 'aktif']);
    barisPenelitian($capaian->id, $pts->id, 'Hibah');

    expect($this->hitung->hitung($capaian->fresh()))->toBeNull(); // belum ada baris jumlah_publikasi

    JumlahPublikasi::create(['tahun_anggaran_id' => $capaian->tahun_anggaran_id, 'capaian_kinerja_id' => $capaian->id, 'jumlah' => 0]);

    expect($this->hitung->hitung($capaian->fresh()))->toBeNull();
});

it('fasilitasi_penelitian tidak memakai jumlah publikasi milik triwulan lain', function () {
    $tw1 = buatCapaianUntukTipe('fasilitasi_penelitian');
    $tw2Id = Triwulan::where('kode', 'TW2')->value('id');
    $tw2 = makeCapaianKinerja($tw1->iku, $tw1->tahunAnggaran, ['triwulan_id' => $tw2Id]);
    $pts = Pts::create(['kode_pts' => 'PTS-F5', 'nama_pts' => 'F5', 'status_pts' => 'aktif']);

    JumlahPublikasi::create(['tahun_anggaran_id' => $tw1->tahun_anggaran_id, 'capaian_kinerja_id' => $tw1->id, 'jumlah' => 5]);
    barisPenelitian($tw2->id, $pts->id, 'Hibah');

    expect($this->hitung->hitung($tw2))->toBeNull();
});

it('constraint DB menolak baris penelitian dengan pts, bentuk, dan tanggal yang sama pada header yang sama', function () {
    $capaian = buatCapaianUntukTipe('fasilitasi_penelitian');
    $pts = Pts::create(['kode_pts' => 'PTS-F6', 'nama_pts' => 'F6', 'status_pts' => 'aktif']);

    barisPenelitian($capaian->id, $pts->id, 'Hibah');
    barisPenelitian($capaian->id, $pts->id, 'Hibah');
})->throws(QueryException::class);

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
