<?php

use App\Models\CapaianKepuasanLayanan;
use App\Models\Triwulan;

beforeEach(function () {
    $this->tahun = makeTahunAnggaran();
    $this->iku = makeIku(makeSasaranKegiatan($this->tahun), ['tipe_iku' => 'kepuasan_layanan']);
    $this->tim = makeTimKerja();
    $this->iku->timKerja()->attach($this->tim->id);
    $this->user = userWithRole('tim_kerja');
    $this->user->timKerja()->attach($this->tim->id);

    $this->tw1 = Triwulan::where('kode', 'TW1')->value('id');
    $this->capaian = makeCapaianKinerja($this->iku, $this->tahun, ['triwulan_id' => $this->tw1]);
    activateTriwulan($this->tahun, 'TW1');
});

it('menampilkan pesan error rule di modal saat input melanggar rule', function () {
    $showUrl = route('tim-kerja.capaian-kinerja.show', $this->iku->id).'?triwulan=TW1';

    $this->actingAs($this->user)->from($showUrl)->post(
        route('tim-kerja.capaian-kinerja.baris.store', [$this->iku->id, 'utama']),
        ['triwulan_id' => $this->tw1, 'total_responden' => 10, 'hasil_perhitungan_kepuasan' => 150]
    )->assertRedirect($showUrl)->assertSessionHasErrors('hasil_perhitungan_kepuasan');

    expect(CapaianKepuasanLayanan::count())->toBe(0);

    // Pesan file ("Ukuran file maksimal 5 MB.") tidak boleh bocor ke rule max non-file.
    $pesan = session('errors')->first('hasil_perhitungan_kepuasan');
    expect($pesan)->not->toContain('Ukuran file');

    // Pesan benar-benar dirender, dan isian lama dipertahankan.
    $this->get($showUrl)->assertOk()->assertSee($pesan)->assertSee('150');
});

it('arsitektur PTS: gagal edit membuka lagi modal yang benar dalam mode edit dengan pesan error', function () {
    $this->iku->update(['tipe_iku' => 'arsitektur_pts']);
    $pts = \App\Models\Pts::create(['kode_pts' => 'PTS-V1', 'nama_pts' => 'V1', 'status_pts' => 'aktif']);
    $baris = $this->capaian->relasi('akreditasi')->create([
        'pts_id' => $pts->id, 'akreditasi' => 'Baik', 'no_sk' => 'SK-1', 'masa_berlaku' => '2027-01-01',
    ]);
    $showUrl = route('tim-kerja.capaian-kinerja.show', $this->iku->id).'?triwulan=TW1';

    $this->actingAs($this->user)->from($showUrl)->post( // seperti browser: POST + _method
        url("tim-kerja/capaian-kinerja/{$this->iku->id}/baris/akreditasi/{$baris->id}"),
        ['_method' => 'PUT', 'triwulan_id' => $this->tw1, 'komponen_form' => 'akreditasi', 'baris_id' => $baris->id,
         'pts_id' => $pts->id, 'akreditasi' => 'Baik', 'no_sk' => 'SK-1', 'masa_berlaku' => 'bukan-tanggal']
    )->assertSessionHasErrors('masa_berlaku');
    $pesan = session('errors')->first('masa_berlaku');

    $this->get($showUrl)->assertOk()
        ->assertSee('modalAkreditasiOpen: true', false)
        ->assertSee("modeAkreditasi: 'edit'", false)
        ->assertSee($pesan);
});
