<?php

use App\Models\CapaianAkreditasiPts;
use App\Models\Pts;
use App\Models\PtsAkreditasi;
use App\Models\Triwulan;

beforeEach(function () {
    config(['pddikti.driver' => 'lldikti12']);

    $this->tahun = makeTahunAnggaran();
    $this->iku = makeIku(makeSasaranKegiatan($this->tahun), ['tipe_iku' => 'arsitektur_pts']);
    $this->tim = makeTimKerja();
    $this->iku->timKerja()->attach($this->tim->id);
    $this->user = userWithRole('tim_kerja');
    $this->user->timKerja()->attach($this->tim->id);

    $this->tw1 = Triwulan::where('kode', 'TW1')->value('id');
    $this->tw2 = Triwulan::where('kode', 'TW2')->value('id');
    $this->capaian = makeCapaianKinerja($this->iku, $this->tahun, ['triwulan_id' => $this->tw1]);
    activateTriwulan($this->tahun, 'TW1');
});

function akreditasiTersinkron(Pts $pts, array $o = []): PtsAkreditasi
{
    return PtsAkreditasi::create($o + [
        'pts_id' => $pts->id, 'peringkat' => 'Baik Sekali', 'no_sk' => 'SK/'.$pts->kode_pts,
        'tgl_sk' => '2022-10-11', 'tgl_akhir' => now()->addYears(2)->toDateString(),
        'status_sk' => 'Aktif', 'terakhir_sinkron_at' => now(),
    ]);
}

function barisAkreditasi($test, Pts $pts, array $o = []): CapaianAkreditasiPts
{
    return CapaianAkreditasiPts::create($o + [
        'capaian_kinerja_id' => $test->capaian->id, 'pts_id' => $pts->id,
        'akreditasi' => 'Baik', 'no_sk' => 'SK-LAMA', 'masa_berlaku' => now()->addYear()->toDateString(),
        'sumber' => 'manual', 'status_validasi' => 'draft',
    ]);
}

function importAkreditasi($test, $iku = null, $triwulanId = null)
{
    return $test->actingAs($test->user)->post(
        route('tim-kerja.capaian-kinerja.import-akreditasi', ($iku ?? $test->iku)->id),
        ['triwulan_id' => $triwulanId ?? $test->tw1]
    );
}

it('menyalin akreditasi tersinkron sebagai draft dengan sumber = driver aktif, tanpa bukti dan tanpa tautan migrasi', function () {
    $pts = makePts('IM1');
    akreditasiTersinkron($pts, ['peringkat' => 'Terakreditasi', 'no_sk' => 'SK/1', 'tgl_akhir' => '2031-01-29']);

    importAkreditasi($this)->assertRedirect()->assertSessionHas('feedback.type', 'success');

    $baris = CapaianAkreditasiPts::sole();
    expect($baris->pts_id)->toBe($pts->id)
        ->and($baris->akreditasi)->toBe('Terakreditasi') // peringkat asli API
        ->and($baris->no_sk)->toBe('SK/1')
        ->and($baris->masa_berlaku->toDateString())->toBe('2031-01-29')
        ->and($baris->sumber)->toBe('lldikti12')
        ->and($baris->status_validasi)->toBe('draft')
        ->and($baris->file_bukti_dukung)->toBeNull()
        ->and($baris->sumber_baris_id)->toBeNull()
        ->and($baris->capaian_kinerja_id)->toBe($this->capaian->id);
});

it('hanya mengimpor PTS aktif & tampil dengan SK lengkap yang belum lewat masa berlakunya', function () {
    $ok = makePts('IM2');
    $hariIni = makePts('IM3');
    foreach ([['IM4', 'tutup'], ['IM5', 'non_aktif'], ['IM6', null]] as [$kode, $status]) {
        akreditasiTersinkron(makePts($kode, ['status_pts' => $status]));
    }
    akreditasiTersinkron(makePts('IM7'), ['tgl_akhir' => now()->subDay()->toDateString()]);
    akreditasiTersinkron(makePts('IM8'), ['no_sk' => null]);
    akreditasiTersinkron(makePts('IM9'), ['tgl_akhir' => null]);
    akreditasiTersinkron($ok);
    akreditasiTersinkron($hariIni, ['tgl_akhir' => today()->toDateString()]); // hari ini = belum lewat

    importAkreditasi($this)->assertSessionHas('feedback.type', 'success');

    expect(CapaianAkreditasiPts::pluck('pts_id')->sort()->values()->all())->toBe([$ok->id, $hariIni->id]);
});

it('import berulang idempoten: tidak membuat baris ganda dan melaporkan "sudah sesuai"', function () {
    akreditasiTersinkron(makePts('ID1'));

    importAkreditasi($this);
    importAkreditasi($this)->assertSessionHas('feedback.type', 'warning')
        ->assertSessionHas('feedback', fn ($f) => str_contains($f['message'], 'sudah sesuai'));

    expect(CapaianAkreditasiPts::count())->toBe(1);
});

it('memperbarui draft bersumber API yang berbeda, memutus tautan migrasi, dan tidak menyentuh file bukti', function () {
    $pts = makePts('UP1');
    $tautan = barisAkreditasi($this, makePts('UP2')); // hanya sebagai id valid untuk sumber_baris_id
    $baris = barisAkreditasi($this, $pts, ['sumber' => 'lldikti12', 'sumber_baris_id' => $tautan->id, 'file_bukti_dukung' => 'x/bukti.pdf']);
    akreditasiTersinkron($pts, ['peringkat' => 'Unggul', 'no_sk' => 'SK-BARU']);

    importAkreditasi($this)->assertSessionHas('feedback.type', 'success');

    $baris->refresh();
    expect($baris->akreditasi)->toBe('Unggul')
        ->and($baris->no_sk)->toBe('SK-BARU')
        ->and($baris->sumber_baris_id)->toBeNull()
        ->and($baris->file_bukti_dukung)->toBe('x/bukti.pdf')
        ->and($baris->status_validasi)->toBe('draft')
        ->and(CapaianAkreditasiPts::count())->toBe(2);
});

it('baris ditolak bersumber API ikut diperbarui dan statusnya tetap ditolak', function () {
    $pts = makePts('RJ1');
    $baris = barisAkreditasi($this, $pts, ['sumber' => 'lldikti12', 'status_validasi' => 'ditolak']);
    akreditasiTersinkron($pts, ['no_sk' => 'SK-BARU']);

    importAkreditasi($this);

    expect($baris->fresh()->no_sk)->toBe('SK-BARU')->and($baris->fresh()->status_validasi)->toBe('ditolak');
});

it('baris manual draft dilewati dan PTS-nya disebut di pesan', function () {
    $pts = makePts('MN1');
    $baris = barisAkreditasi($this, $pts, ['sumber' => 'manual']);
    akreditasiTersinkron($pts, ['no_sk' => 'SK-API']);

    importAkreditasi($this)->assertSessionHas('feedback', fn ($f) => str_contains($f['message'], 'PTS MN1'));

    expect($baris->fresh()->no_sk)->toBe('SK-LAMA')->and($baris->fresh()->sumber)->toBe('manual');
});

it('baris menunggu_validasi dan disetujui tidak disentuh', function () {
    $a = makePts('LK1');
    $b = makePts('LK2');
    $menunggu = barisAkreditasi($this, $a, ['sumber' => 'lldikti12', 'status_validasi' => 'menunggu_validasi']);
    $disetujui = barisAkreditasi($this, $b, ['sumber' => 'lldikti12', 'status_validasi' => 'disetujui']);
    akreditasiTersinkron($a, ['no_sk' => 'SK-API']);
    akreditasiTersinkron($b, ['no_sk' => 'SK-API']);

    importAkreditasi($this)->assertSessionHas('feedback.type', 'warning');

    expect($menunggu->fresh()->no_sk)->toBe('SK-LAMA')->and($disetujui->fresh()->no_sk)->toBe('SK-LAMA');
});

it('edit Tim Kerja pada baris hasil import mengubah sumber menjadi manual, dan import berikutnya melewatinya', function () {
    $pts = makePts('ED1');
    akreditasiTersinkron($pts);
    importAkreditasi($this);
    $baris = CapaianAkreditasiPts::sole();

    $this->actingAs($this->user)->put(
        route('tim-kerja.capaian-kinerja.baris.update', ['iku' => $this->iku->id, 'komponen' => 'akreditasi', 'barisId' => $baris->id]),
        ['triwulan_id' => $this->tw1, 'pts_id' => $pts->id, 'akreditasi' => 'Baik', 'no_sk' => 'SK-EDIT', 'masa_berlaku' => now()->addYear()->toDateString()]
    )->assertRedirect()->assertSessionHas('feedback.type', 'success');

    expect($baris->fresh()->sumber)->toBe('manual');

    importAkreditasi($this)->assertSessionHas('feedback', fn ($f) => str_contains($f['message'], 'PTS ED1'));
    expect($baris->fresh()->no_sk)->toBe('SK-EDIT');
});

it('tanpa data akreditasi hasil sinkronisasi, menampilkan galat yang jelas', function () {
    importAkreditasi($this)->assertSessionHas('feedback.type', 'error');

    expect(CapaianAkreditasiPts::count())->toBe(0);
});

it('ditolak di Triwulan yang tidak aktif', function () {
    makeCapaianKinerja($this->iku, $this->tahun, ['triwulan_id' => $this->tw2]);
    akreditasiTersinkron(makePts('TA1'));

    importAkreditasi($this, null, $this->tw2)->assertForbidden();

    expect(CapaianAkreditasiPts::count())->toBe(0);
});

it('IKU selain arsitektur_pts mengembalikan 404', function () {
    $lain = makeIku(makeSasaranKegiatan($this->tahun, 'Sasaran Lain'), ['tipe_iku' => 'fasilitasi_mutu_pts']);
    $lain->timKerja()->attach($this->tim->id);
    makeCapaianKinerja($lain, $this->tahun, ['triwulan_id' => $this->tw1]);

    importAkreditasi($this, $lain)->assertNotFound();
});

it('Tim Kerja yang bukan pemilik IKU ditolak', function () {
    akreditasiTersinkron(makePts('OW1'));
    $asing = userWithRole('tim_kerja');

    $this->actingAs($asing)->post(
        route('tim-kerja.capaian-kinerja.import-akreditasi', $this->iku->id),
        ['triwulan_id' => $this->tw1]
    )->assertForbidden();

    expect(CapaianAkreditasiPts::count())->toBe(0);
});

it('halaman Arsitektur PTS: tombol import hanya di Triwulan aktif, dropdown tanpa PTS tutup kecuali yang sudah terpakai', function () {
    makePts('IA1');
    makePts('IT1', ['status_pts' => 'tutup']);
    $tutupTerpakai = makePts('IT2', ['status_pts' => 'tutup']);
    barisAkreditasi($this, $tutupTerpakai);
    $show = route('tim-kerja.capaian-kinerja.show', $this->iku->id);

    $this->actingAs($this->user)->get($show.'?triwulan=TW1')
        ->assertOk()
        ->assertSee('Import dari LLDikti 12')
        ->assertSee('IA1 — PTS IA1', false)
        ->assertDontSee('IT1 — PTS IT1', false)
        ->assertSee('IT2 — PTS IT2', false);

    $this->actingAs($this->user)->get($show.'?triwulan=TW2')
        ->assertOk()
        ->assertDontSee('Import dari LLDikti 12');
});