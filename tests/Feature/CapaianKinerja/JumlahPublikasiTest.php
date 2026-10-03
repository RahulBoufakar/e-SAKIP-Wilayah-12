<?php

use App\Events\ActivityOccurred;
use App\Models\CapaianFasilitasiPenelitian;
use App\Models\JumlahPublikasi;
use App\Models\Pts;
use App\Models\Triwulan;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->tahun = makeTahunAnggaran();
    $this->iku = makeIku(makeSasaranKegiatan($this->tahun), ['tipe_iku' => 'fasilitasi_penelitian']);
    $this->tim = makeTimKerja();
    $this->iku->timKerja()->attach($this->tim->id);
    $this->user = userWithRole('tim_kerja');
    $this->user->timKerja()->attach($this->tim->id);

    $this->tw1 = Triwulan::where('kode', 'TW1')->value('id');
    $this->capaian = makeCapaianKinerja($this->iku, $this->tahun, ['triwulan_id' => $this->tw1]);
    activateTriwulan($this->tahun, 'TW1');
});

function simpanPublikasi($test, $jumlah, $user = null)
{
    return $test->actingAs($user ?? $test->user)->put(
        route('tim-kerja.capaian-kinerja.jumlah-publikasi.update', $test->iku->id),
        ['triwulan_id' => $test->tw1, 'jumlah' => $jumlah]
    );
}

it('menyimpan jumlah publikasi, mencatat siapa yang memperbarui, dan audit nilai lama → baru', function () {
    Event::fake([ActivityOccurred::class]);

    simpanPublikasi($this, 5)->assertRedirect();
    simpanPublikasi($this, 8)->assertRedirect();

    $baris = JumlahPublikasi::where('capaian_kinerja_id', $this->capaian->id)->get();

    expect($baris)->toHaveCount(1)
        ->and($baris->first()->jumlah)->toEqual(8)
        ->and($baris->first()->diperbarui_oleh)->toEqual($this->user->id)
        ->and($baris->first()->tahun_anggaran_id)->toEqual($this->tahun->id);

    Event::assertDispatched(ActivityOccurred::class, fn ($e) => $e->properties === ['jumlah_lama' => null, 'jumlah_baru' => 5]);
    Event::assertDispatched(ActivityOccurred::class, fn ($e) => $e->properties === ['jumlah_lama' => 5, 'jumlah_baru' => 8]);
});

it('terkunci saat status header menunggu_validasi atau disetujui', function (string $status) {
    $this->capaian->update(['status' => $status]);

    simpanPublikasi($this, 5)->assertSessionHas('feedback.type', 'error');

    expect(JumlahPublikasi::count())->toBe(0);
})->with(['menunggu_validasi', 'disetujui']);

it('boleh diubah lagi saat header ditolak', function () {
    $this->capaian->update(['status' => 'ditolak']);

    simpanPublikasi($this, 3)->assertSessionHas('feedback.type', 'success');

    expect(JumlahPublikasi::where('capaian_kinerja_id', $this->capaian->id)->value('jumlah'))->toEqual(3);
});

it('menolak simpan di luar triwulan aktif (403)', function () {
    activateTriwulan($this->tahun, 'TW2');

    simpanPublikasi($this, 5)->assertForbidden();
});

it('menolak tim kerja yang bukan penanggung jawab IKU (403)', function () {
    $lain = userWithRole('tim_kerja', ['email' => 'lain-pub@test.local']);
    $lain->timKerja()->attach(makeTimKerja('Tim Lain Pub')->id);

    simpanPublikasi($this, 5, $lain)->assertForbidden();
});

it('menolak jumlah negatif atau non-bilangan-bulat', function () {
    simpanPublikasi($this, -1)->assertSessionHasErrorsIn('jumlahPublikasi', 'jumlah');
    simpanPublikasi($this, 'abc')->assertSessionHasErrorsIn('jumlahPublikasi', 'jumlah');
});

it('migrasi triwulan menyalin baris tabel utama tetapi tidak menyalin jumlah publikasi', function () {
    $tw2Id = Triwulan::where('kode', 'TW2')->value('id');
    $capaian2 = makeCapaianKinerja($this->iku, $this->tahun, ['triwulan_id' => $tw2Id]);
    $pts = Pts::create(['kode_pts' => 'PTS-JP1', 'nama_pts' => 'JP1', 'status_pts' => 'aktif']);

    CapaianFasilitasiPenelitian::create([
        'capaian_kinerja_id' => $this->capaian->id, 'pts_id' => $pts->id,
        'bentuk_fasilitasi' => 'Hibah', 'tanggal_kegiatan' => '2026-03-10',
        'status_validasi' => 'disetujui',
    ]);
    JumlahPublikasi::create(['tahun_anggaran_id' => $this->tahun->id, 'capaian_kinerja_id' => $this->capaian->id, 'jumlah' => 7]);
    activateTriwulan($this->tahun, 'TW2');

    $this->actingAs($this->user)->post(
        route('tim-kerja.capaian-kinerja.migrasi-triwulan', $this->iku->id),
        ['triwulan_id' => $tw2Id]
    )->assertRedirect();

    expect($capaian2->fasilitasiPenelitian()->count())->toBe(1)
        ->and(JumlahPublikasi::where('capaian_kinerja_id', $capaian2->id)->exists())->toBeFalse()
        ->and(JumlahPublikasi::count())->toBe(1);
});