<?php

use App\Models\CapaianDosenNaikJafung;
use App\Models\CapaianFasilitasiMutuPts;
use App\Models\Pts;
use App\Models\Triwulan;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('private');

    $this->tahun = makeTahunAnggaran();
    $this->iku = makeIku(makeSasaranKegiatan($this->tahun), ['tipe_iku' => 'fasilitasi_mutu_pts']);
    $this->tim = makeTimKerja();
    $this->iku->timKerja()->attach($this->tim->id);
    $this->user = userWithRole('tim_kerja');
    $this->user->timKerja()->attach($this->tim->id);

    $this->tw1 = Triwulan::where('kode', 'TW1')->value('id');
    $this->tw2 = Triwulan::where('kode', 'TW2')->value('id');
    $this->capaian1 = makeCapaianKinerja($this->iku, $this->tahun, ['triwulan_id' => $this->tw1]);
    $this->capaian2 = makeCapaianKinerja($this->iku, $this->tahun, ['triwulan_id' => $this->tw2]);

    $this->pts = Pts::create(['kode_pts' => 'PTS-M1', 'nama_pts' => 'M1', 'status_pts' => 'aktif']);
    $this->path = 'capaian-kinerja-hybrid/bukti-tw1.pdf';
    Storage::disk('private')->put($this->path, 'pdf');

    CapaianFasilitasiMutuPts::create([
        'capaian_kinerja_id' => $this->capaian1->id, 'pts_id' => $this->pts->id,
        'bentuk_fasilitasi' => 'Pelatihan A', 'tanggal_kegiatan' => now(),
        'file_bukti_dukung' => $this->path, 'status_validasi' => 'disetujui',
    ]);

    activateTriwulan($this->tahun, 'TW2');
});

function migrasiTw2($test, $iku)
{
    return $test->actingAs($test->user)->post(
        route('tim-kerja.capaian-kinerja.migrasi-triwulan', $iku->id),
        ['triwulan_id' => $test->tw2]
    );
}

function kirimTw2($test)
{
    return $test->actingAs($test->user)->put(
        route('tim-kerja.capaian-kinerja.kirim', $test->iku->id),
        ['triwulan_id' => $test->tw2]
    );
}

/** IKU jafung (kunci = nidn) + header TW1 & TW2. @return array{0: \App\Models\Iku, 1: \App\Models\CapaianKinerja, 2: \App\Models\CapaianKinerja} */
function siapkanJafung($test): array
{
    $iku = makeIku(makeSasaranKegiatan($test->tahun, 'Sasaran Jafung'), ['tipe_iku' => 'dosen_naik_jafung']);
    $iku->timKerja()->attach($test->tim->id);

    return [
        $iku,
        makeCapaianKinerja($iku, $test->tahun, ['triwulan_id' => $test->tw1]),
        makeCapaianKinerja($iku, $test->tahun, ['triwulan_id' => $test->tw2]),
    ];
}

function barisJafung($test, int $capaianId, string $nidn, array $attrs = []): CapaianDosenNaikJafung
{
    return CapaianDosenNaikJafung::create($attrs + [
        'capaian_kinerja_id' => $capaianId, 'pts_id' => $test->pts->id,
        'nama_dosen' => 'Dosen '.$nidn, 'nidn' => $nidn,
        'jenjang_asal' => 'asisten_ahli', 'jenjang_baru' => 'lektor',
        'no_sk' => 'SK-'.$nidn, 'tanggal_sk' => '2026-02-01',
        'file_bukti_dukung' => 'capaian-kinerja-hybrid/sk-'.$nidn.'.pdf',
        'status_validasi' => 'disetujui',
    ]);
}

// --- Dasar: salin + shared reference ---

it('menyalin baris disetujui sebagai draft, bertaut ke asal, dengan path file yang sama (tanpa duplikasi fisik)', function () {
    migrasiTw2($this, $this->iku)->assertRedirect();

    $asal = CapaianFasilitasiMutuPts::where('capaian_kinerja_id', $this->capaian1->id)->first();
    $baris = $this->capaian2->fasilitasiMutuPts()->get();

    expect($baris)->toHaveCount(1)
        ->and($baris->first()->status_validasi)->toBe('draft')
        ->and($baris->first()->sumber_baris_id)->toBe($asal->id)
        ->and($baris->first()->file_bukti_dukung)->toBe($this->path)
        ->and(Storage::disk('private')->allFiles())->toHaveCount(1);
});

it('menghapus baris hasil migrasi tidak menghapus file yang masih dipakai triwulan asal', function () {
    migrasiTw2($this, $this->iku);
    $baris = $this->capaian2->fasilitasiMutuPts()->first();

    $this->actingAs($this->user)->delete(route('tim-kerja.capaian-kinerja.baris.destroy', [
        'iku' => $this->iku->id, 'komponen' => 'utama', 'barisId' => $baris->id, 'triwulan_id' => $this->tw2,
    ]));

    $this->assertDatabaseMissing('capaian_fasilitasi_mutu_pts', ['id' => $baris->id]);
    Storage::disk('private')->assertExists($this->path);
});

it('fileTersedia() false (tanpa error) saat path terisi tetapi file fisik hilang', function () {
    Storage::disk('private')->delete($this->path);

    expect(CapaianFasilitasiMutuPts::first()->fileTersedia())->toBeFalse();
});

// --- Lewati yang sudah ada ---

it('migrasi ulang hanya menambah baris baru dan idempoten', function () {
    migrasiTw2($this, $this->iku);

    $pts2 = Pts::create(['kode_pts' => 'PTS-M2', 'nama_pts' => 'M2', 'status_pts' => 'aktif']);
    CapaianFasilitasiMutuPts::create([
        'capaian_kinerja_id' => $this->capaian1->id, 'pts_id' => $pts2->id,
        'bentuk_fasilitasi' => 'Pelatihan B', 'tanggal_kegiatan' => now(),
        'file_bukti_dukung' => $this->path, 'status_validasi' => 'disetujui',
    ]);

    migrasiTw2($this, $this->iku);
    migrasiTw2($this, $this->iku); // klik ketiga: tidak ada yang baru

    expect($this->capaian2->fasilitasiMutuPts()->count())->toBe(2);
});

it('tidak menimpa baris mandiri triwulan tujuan yang kuncinya sama, apa pun statusnya', function () {
    $src = CapaianFasilitasiMutuPts::first();
    CapaianFasilitasiMutuPts::create([
        'capaian_kinerja_id' => $this->capaian2->id, 'pts_id' => $src->pts_id,
        'bentuk_fasilitasi' => $src->bentuk_fasilitasi, 'tanggal_kegiatan' => $src->getRawOriginal('tanggal_kegiatan'),
        'file_bukti_dukung' => 'capaian-kinerja-hybrid/unggahan-tw2.pdf', 'status_validasi' => 'ditolak',
    ]);

    migrasiTw2($this, $this->iku)->assertRedirect();

    $baris = $this->capaian2->fasilitasiMutuPts()->get();
    expect($baris)->toHaveCount(1)
        ->and($baris->first()->file_bukti_dukung)->toBe('capaian-kinerja-hybrid/unggahan-tw2.pdf')
        ->and($baris->first()->status_validasi)->toBe('ditolak')
        ->and($baris->first()->sumber_baris_id)->toBeNull();
});

it('melewati dosen dengan NIDN yang sudah ada sebagai baris mandiri di triwulan tujuan', function () {
    [$iku, $cap1, $cap2] = siapkanJafung($this);
    barisJafung($this, $cap1->id, '001');
    barisJafung($this, $cap1->id, '002');
    barisJafung($this, $cap2->id, '001', ['no_sk' => 'SK-1-REVISI', 'status_validasi' => 'draft']);

    migrasiTw2($this, $iku)->assertRedirect();

    expect($cap2->dosenNaikJafung()->count())->toBe(2)
        ->and($cap2->dosenNaikJafung()->where('nidn', '001')->value('no_sk'))->toBe('SK-1-REVISI');
});

// --- Sinkronisasi lewat sumber_baris_id ---

it('memperbarui salinan draft saat kolom non-kunci di triwulan asal direvisi', function () {
    [$iku, $cap1, $cap2] = siapkanJafung($this);
    $asal = barisJafung($this, $cap1->id, '001');
    migrasiTw2($this, $iku);

    $asal->update(['no_sk' => 'SK-REVISI']);
    migrasiTw2($this, $iku);

    expect($cap2->dosenNaikJafung()->count())->toBe(1)
        ->and($cap2->dosenNaikJafung()->first()->no_sk)->toBe('SK-REVISI');
});

it('koreksi kolom kunci (NIDN) di triwulan asal memperbarui salinan, bukan membuat baris ganda', function () {
    [$iku, $cap1, $cap2] = siapkanJafung($this);
    $asal = barisJafung($this, $cap1->id, '001');
    migrasiTw2($this, $iku);

    $asal->update(['nidn' => '010']);
    migrasiTw2($this, $iku);

    expect($cap2->dosenNaikJafung()->count())->toBe(1)
        ->and($cap2->dosenNaikJafung()->first()->nidn)->toBe('010');
});

it('salinan yang sudah disetujui hanya dilaporkan, tidak diubah', function () {
    [$iku, $cap1, $cap2] = siapkanJafung($this);
    $asal = barisJafung($this, $cap1->id, '001');
    migrasiTw2($this, $iku);

    $salinan = $cap2->dosenNaikJafung()->first();
    $salinan->update(['status_validasi' => 'disetujui']);
    $asal->update(['no_sk' => 'SK-REVISI']);

    migrasiTw2($this, $iku)->assertSessionHas('feedback.type', 'error');

    expect($salinan->fresh()->no_sk)->toBe('SK-001');
});

it('bentrok kunci saat sinkronisasi dilaporkan tanpa error dan tidak mengubah data', function () {
    [$iku, $cap1, $cap2] = siapkanJafung($this);
    $asal = barisJafung($this, $cap1->id, '001');
    migrasiTw2($this, $iku);

    barisJafung($this, $cap2->id, '010', ['status_validasi' => 'draft']); // baris mandiri memegang kunci baru
    $asal->update(['nidn' => '010']);

    migrasiTw2($this, $iku)->assertSessionHas('feedback.type', 'error');

    expect($cap2->dosenNaikJafung()->pluck('nidn')->sort()->values()->all())->toBe(['001', '010']);
});

it('edit di triwulan tujuan memutus tautan dan tidak mengganggu file/baris triwulan asal', function () {
    migrasiTw2($this, $this->iku);
    $salinan = $this->capaian2->fasilitasiMutuPts()->first();
    $asal = CapaianFasilitasiMutuPts::where('capaian_kinerja_id', $this->capaian1->id)->first();

    $this->actingAs($this->user)->put(
        route('tim-kerja.capaian-kinerja.baris.update', ['iku' => $this->iku->id, 'komponen' => 'utama', 'barisId' => $salinan->id]),
        [
            'triwulan_id' => $this->tw2,
            'pts_id' => $this->pts->id,
            'bentuk_fasilitasi' => 'Pelatihan A (revisi)',
            'tanggal_kegiatan' => '2026-05-01',
            'file_bukti_dukung' => UploadedFile::fake()->create('baru.pdf', 50, 'application/pdf'),
        ]
    )->assertRedirect();

    $salinan = $salinan->fresh();

    expect($salinan->sumber_baris_id)->toBeNull()
        ->and($salinan->file_bukti_dukung)->not->toBe($this->path)
        ->and($asal->fresh()->file_bukti_dukung)->toBe($this->path);
    Storage::disk('private')->assertExists($salinan->file_bukti_dukung);
    Storage::disk('private')->assertExists($this->path);
});

// --- Bukti dukung saat kirim ---

it('kirim diblokir selama bukti wajib tidak tersedia', function () {
    migrasiTw2($this, $this->iku);
    Storage::disk('private')->delete($this->path); // path di TW2 menggantung

    kirimTw2($this)->assertSessionHas('feedback.type', 'error');

    expect($this->capaian2->fasilitasiMutuPts()->first()->status_validasi)->toBe('draft');
});

it('kirim berjalan bila bukti wajib tersedia', function () {
    migrasiTw2($this, $this->iku);

    kirimTw2($this)->assertSessionHas('feedback.type', 'success');

    expect($this->capaian2->fasilitasiMutuPts()->first()->status_validasi)->toBe('menunggu_validasi');
});

// --- Regresi refaktor kunciUnik() -> aturanUnik() ---

it('tetap menolak NIDN duplikat pada triwulan yang sama lewat aturanUnik()', function () {
    [$iku, , $cap2] = siapkanJafung($this);
    barisJafung($this, $cap2->id, '001', ['status_validasi' => 'draft']);

    $this->actingAs($this->user)->post(
        route('tim-kerja.capaian-kinerja.baris.store', ['iku' => $iku->id, 'komponen' => 'utama']),
        [
            'triwulan_id' => $this->tw2, 'pts_id' => $this->pts->id,
            'nama_dosen' => 'Dosen X', 'nidn' => '001', 'tipe_kepegawaian' => 'Dosen PNS',
            'jenjang_asal' => 'asisten_ahli', 'jenjang_baru' => 'lektor',
            'no_sk' => 'SK-X', 'tanggal_sk' => '2026-02-01',
            'file_bukti_dukung' => UploadedFile::fake()->create('sk.pdf', 50, 'application/pdf'),
        ]
    )->assertSessionHasErrors('nidn');
});