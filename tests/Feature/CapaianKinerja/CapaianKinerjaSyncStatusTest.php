<?php

use App\Models\CapaianAkreditasiPts;
use App\Models\CapaianFasilitasiMutuPts;
use App\Models\CapaianKepuasanLayanan;
use App\Models\CapaianPenggabunganPts;
use App\Models\Pts;

it('status header tetap draft saat belum ada baris sama sekali', function () {
    $tahun = makeTahunAnggaran();
    $sasaran = makeSasaranKegiatan($tahun);
    $iku = makeIku($sasaran, ['tipe_iku' => 'kepuasan_layanan']);
    $capaian = makeCapaianKinerja($iku, $tahun);

    $capaian->syncStatusFromBaris();

    expect($capaian->status)->toBe('draft');
});

it('status header disetujui hanya jika SEMUA baris disetujui (Spek §7)', function () {
    $tahun = makeTahunAnggaran();
    $sasaran = makeSasaranKegiatan($tahun);
    $iku = makeIku($sasaran, ['tipe_iku' => 'fasilitasi_mutu_pts']);
    $capaian = makeCapaianKinerja($iku, $tahun);
    $pts1 = Pts::create(['kode_pts' => 'PTS-S1', 'nama_pts' => 'S1', 'status_pts' => 'aktif']);
    $pts2 = Pts::create(['kode_pts' => 'PTS-S2', 'nama_pts' => 'S2', 'status_pts' => 'aktif']);

    CapaianFasilitasiMutuPts::create(['capaian_kinerja_id' => $capaian->id, 'pts_id' => $pts1->id, 'bentuk_fasilitasi' => 'Pelatihan A', 'tanggal_kegiatan' => now(), 'file_bukti_dukung' => 'bukti1.pdf', 'status_validasi' => 'disetujui']);
    $capaian->syncStatusFromBaris();
    expect($capaian->fresh()->status)->toBe('disetujui');

    CapaianFasilitasiMutuPts::create(['capaian_kinerja_id' => $capaian->id, 'pts_id' => $pts2->id, 'bentuk_fasilitasi' => 'Pelatihan B', 'tanggal_kegiatan' => now(), 'file_bukti_dukung' => 'bukti2.pdf', 'status_validasi' => 'menunggu_validasi']);
    $capaian->syncStatusFromBaris();
    expect($capaian->fresh()->status)->toBe('menunggu_validasi');
});

it('status header ditolak jika ada satu saja baris ditolak, walau baris lain sudah disetujui', function () {
    $tahun = makeTahunAnggaran();
    $sasaran = makeSasaranKegiatan($tahun);
    $iku = makeIku($sasaran, ['tipe_iku' => 'fasilitasi_mutu_pts']);
    $capaian = makeCapaianKinerja($iku, $tahun);
    $pts1 = Pts::create(['kode_pts' => 'PTS-S3', 'nama_pts' => 'S3', 'status_pts' => 'aktif']);
    $pts2 = Pts::create(['kode_pts' => 'PTS-S4', 'nama_pts' => 'S4', 'status_pts' => 'aktif']);

    CapaianFasilitasiMutuPts::create(['capaian_kinerja_id' => $capaian->id, 'pts_id' => $pts1->id, 'bentuk_fasilitasi' => 'Pelatihan A', 'tanggal_kegiatan' => now(), 'file_bukti_dukung' => 'bukti1.pdf', 'status_validasi' => 'disetujui']);
    CapaianFasilitasiMutuPts::create(['capaian_kinerja_id' => $capaian->id, 'pts_id' => $pts2->id, 'bentuk_fasilitasi' => 'Pelatihan B', 'tanggal_kegiatan' => now(), 'file_bukti_dukung' => 'bukti2.pdf', 'status_validasi' => 'ditolak']);

    $capaian->syncStatusFromBaris();

    expect($capaian->status)->toBe('ditolak');
});

it('status header untuk arsitektur_pts mempertimbangkan KEDUA tabel sekaligus (akreditasi + penggabungan)', function () {
    $tahun = makeTahunAnggaran();
    $sasaran = makeSasaranKegiatan($tahun);
    $iku = makeIku($sasaran, ['tipe_iku' => 'arsitektur_pts']);
    $capaian = makeCapaianKinerja($iku, $tahun);
    $pts = Pts::create(['kode_pts' => 'PTS-G1', 'nama_pts' => 'G1', 'status_pts' => 'aktif']);

    CapaianAkreditasiPts::create(['capaian_kinerja_id' => $capaian->id, 'pts_id' => $pts->id, 'akreditasi' => 'Baik', 'no_sk' => 'SK1', 'masa_berlaku' => now()->addYear(), 'status_validasi' => 'disetujui']);
    CapaianPenggabunganPts::create(['capaian_kinerja_id' => $capaian->id, 'pts_id' => $pts->id, 'sk_penggabungan' => 'SK2', 'status_validasi' => 'menunggu_validasi']);

    $capaian->syncStatusFromBaris();

    // akreditasi sudah disetujui, TAPI penggabungan masih menunggu -> header belum disetujui
    expect($capaian->status)->toBe('menunggu_validasi');
});

it('getCanKirimAttribute() true hanya jika ada baris draft/ditolak yang siap dikirim', function () {
    $tahun = makeTahunAnggaran();
    $sasaran = makeSasaranKegiatan($tahun);
    $iku = makeIku($sasaran, ['tipe_iku' => 'kepuasan_layanan']);
    $capaian = makeCapaianKinerja($iku, $tahun);

    expect($capaian->can_kirim)->toBeFalse();

    CapaianKepuasanLayanan::create(['capaian_kinerja_id' => $capaian->id, 'total_responden' => 10, 'hasil_perhitungan_kepuasan' => 80, 'status_validasi' => 'draft']);

    expect($capaian->fresh()->can_kirim)->toBeTrue();
});

it('getCanKirimAttribute() false jika seluruh baris sudah menunggu_validasi/disetujui', function () {
    $tahun = makeTahunAnggaran();
    $sasaran = makeSasaranKegiatan($tahun);
    $iku = makeIku($sasaran, ['tipe_iku' => 'kepuasan_layanan']);
    $capaian = makeCapaianKinerja($iku, $tahun);

    CapaianKepuasanLayanan::create(['capaian_kinerja_id' => $capaian->id, 'total_responden' => 10, 'hasil_perhitungan_kepuasan' => 80, 'status_validasi' => 'disetujui']);

    expect($capaian->fresh()->can_kirim)->toBeFalse();
});

it('membatasi realisasi maksimal sama dengan Target PK', function () {
    $tahun = makeTahunAnggaran();
    $iku = makeIku(makeSasaranKegiatan($tahun), ['tipe_iku' => 'nilai_rka', 'target_pk' => 97.72]);
    $capaian = makeCapaianKinerja($iku, $tahun);
    \App\Models\CapaianNilaiRka::create(['capaian_kinerja_id' => $capaian->id, 'nilai_rka' => 97.83, 'status_validasi' => 'disetujui']);

    expect(app(\App\Services\CapaianKinerjaHitungService::class)->hitung($capaian))->toBe(97.72);
});

it('menolak baris kedua pada IKU entri tunggal (constraint DB)', function () {
    $tahun = makeTahunAnggaran();
    $iku = makeIku(makeSasaranKegiatan($tahun), ['tipe_iku' => 'nilai_rka']);
    $capaian = makeCapaianKinerja($iku, $tahun);
    \App\Models\CapaianNilaiRka::create(['capaian_kinerja_id' => $capaian->id, 'nilai_rka' => 80, 'status_validasi' => 'draft']);

    \App\Models\CapaianNilaiRka::create(['capaian_kinerja_id' => $capaian->id, 'nilai_rka' => 90, 'status_validasi' => 'draft']);
})->throws(\Illuminate\Database\QueryException::class);
