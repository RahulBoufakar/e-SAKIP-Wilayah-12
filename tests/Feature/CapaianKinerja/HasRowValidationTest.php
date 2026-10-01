<?php

use App\Models\CapaianKepuasanLayanan;
use Illuminate\Auth\Access\AuthorizationException;

beforeEach(function () {
    $tahun = makeTahunAnggaran();
    $sasaran = makeSasaranKegiatan($tahun);
    $iku = makeIku($sasaran, ['tipe_iku' => 'kepuasan_layanan']);
    $this->capaian = makeCapaianKinerja($iku, $tahun);
});

function buatBarisKepuasan(int $capaianId, string $status): CapaianKepuasanLayanan
{
    return CapaianKepuasanLayanan::create([
        'capaian_kinerja_id' => $capaianId,
        'total_responden' => 10,
        'hasil_perhitungan_kepuasan' => 80,
        'status_validasi' => $status,
    ]);
}

it('kirim() memindahkan draft/ditolak ke menunggu_validasi dan mengosongkan catatan_revisi', function () {
    $baris = buatBarisKepuasan($this->capaian->id, 'ditolak');
    $baris->catatan_revisi = 'Revisi sebelumnya';
    $baris->save();

    $baris->kirim();

    expect($baris->status_validasi)->toBe('menunggu_validasi')
        ->and($baris->catatan_revisi)->toBeNull();
});

it('menolak kirim() jika status bukan draft/ditolak', function () {
    $baris = buatBarisKepuasan($this->capaian->id, 'menunggu_validasi');

    $baris->kirim();
})->throws(RuntimeException::class, 'terkunci');

it('setujui() hanya bisa dilakukan role validator/admin/super_admin', function () {
    $this->actingAs(userWithRole('tim_kerja'));
    $baris = buatBarisKepuasan($this->capaian->id, 'menunggu_validasi');

    $baris->setujui();
})->throws(AuthorizationException::class);

it('setujui() memindahkan menunggu_validasi ke disetujui untuk validator', function () {
    $this->actingAs(userWithRole('validator'));
    $baris = buatBarisKepuasan($this->capaian->id, 'menunggu_validasi');

    $baris->setujui();

    expect($baris->status_validasi)->toBe('disetujui');
});

it('menolak setujui() kedua kali pada baris yang sama (guard transisi ganda)', function () {
    $this->actingAs(userWithRole('validator'));
    $baris = buatBarisKepuasan($this->capaian->id, 'menunggu_validasi');

    $baris->setujui();
    $baris->setujui();
})->throws(RuntimeException::class, 'Hanya baris berstatus menunggu_validasi yang bisa disetujui');

it('tolak() mewajibkan catatan_revisi dan memindahkan ke ditolak', function () {
    $this->actingAs(userWithRole('validator'));
    $baris = buatBarisKepuasan($this->capaian->id, 'menunggu_validasi');

    $baris->tolak('Data tidak sesuai bukti dukung');

    expect($baris->status_validasi)->toBe('ditolak')
        ->and($baris->catatan_revisi)->toBe('Data tidak sesuai bukti dukung');
});

it('melempar error saat tolak() dipanggil dengan catatan_revisi kosong', function () {
    $this->actingAs(userWithRole('validator'));
    $baris = buatBarisKepuasan($this->capaian->id, 'menunggu_validasi');

    $baris->tolak('   ');
})->throws(InvalidArgumentException::class);

it('mengunci baris untuk Tim Kerja saat disetujui, kecuali untuk super_admin', function () {
    $baris = buatBarisKepuasan($this->capaian->id, 'disetujui');

    $this->actingAs(userWithRole('tim_kerja'));
    expect($baris->isFieldLocked())->toBeTrue();

    $this->actingAs(userWithRole('super_admin'));
    expect($baris->isFieldLocked())->toBeFalse();
});

it('baris berstatus draft tidak terkunci', function () {
    $baris = buatBarisKepuasan($this->capaian->id, 'draft');

    expect($baris->isFieldLocked())->toBeFalse();
});

it('scope disetujui() dan menungguValidasi() memfilter dengan benar', function () {
    $tahun = makeTahunAnggaran(2027);
    $sasaran = makeSasaranKegiatan($tahun);
    $iku = makeIku($sasaran, ['tipe_iku' => 'fasilitasi_mutu_pts']);
    $capaian = makeCapaianKinerja($iku, $tahun);
    $pts1 = \App\Models\Pts::create(['kode_pts' => 'PTS-R1', 'nama_pts' => 'R1', 'status_pts' => 'aktif']);
    $pts2 = \App\Models\Pts::create(['kode_pts' => 'PTS-R2', 'nama_pts' => 'R2', 'status_pts' => 'aktif']);
    $pts3 = \App\Models\Pts::create(['kode_pts' => 'PTS-R3', 'nama_pts' => 'R3', 'status_pts' => 'aktif']);
    $pts4 = \App\Models\Pts::create(['kode_pts' => 'PTS-R4', 'nama_pts' => 'R4', 'status_pts' => 'aktif']);

    \App\Models\CapaianFasilitasiMutuPts::create(['capaian_kinerja_id' => $capaian->id, 'pts_id' => $pts1->id, 'bentuk_fasilitasi' => 'Pelatihan A', 'tanggal_kegiatan' => now(), 'file_bukti_dukung' => 'a.pdf', 'status_validasi' => 'disetujui']);
    \App\Models\CapaianFasilitasiMutuPts::create(['capaian_kinerja_id' => $capaian->id, 'pts_id' => $pts2->id, 'bentuk_fasilitasi' => 'Pelatihan B', 'tanggal_kegiatan' => now(), 'file_bukti_dukung' => 'b.pdf', 'status_validasi' => 'disetujui']);
    \App\Models\CapaianFasilitasiMutuPts::create(['capaian_kinerja_id' => $capaian->id, 'pts_id' => $pts3->id, 'bentuk_fasilitasi' => 'Pelatihan C', 'tanggal_kegiatan' => now(), 'file_bukti_dukung' => 'c.pdf', 'status_validasi' => 'menunggu_validasi']);
    \App\Models\CapaianFasilitasiMutuPts::create(['capaian_kinerja_id' => $capaian->id, 'pts_id' => $pts4->id, 'bentuk_fasilitasi' => 'Pelatihan D', 'tanggal_kegiatan' => now(), 'file_bukti_dukung' => 'd.pdf', 'status_validasi' => 'draft']);

    expect(\App\Models\CapaianFasilitasiMutuPts::disetujui()->count())->toBe(2)
        ->and(\App\Models\CapaianFasilitasiMutuPts::menungguValidasi()->count())->toBe(1);
});
