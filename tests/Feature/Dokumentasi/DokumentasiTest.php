<?php

use App\Models\CapaianFasilitasiMutuPts;
use App\Models\DetailKegiatan;
use App\Models\LaporanKegiatan;
use App\Models\Pts;
use App\Models\Triwulan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function () {
    Storage::fake('private');
    $this->tahun = makeTahunAnggaran(2026);
    $this->iku = makeIku(makeSasaranKegiatan($this->tahun));
});

function dokumentasiBody($response): string
{
    ob_start();
    $response->baseResponse->sendContent();

    return ob_get_clean();
}

function dokumentasiUsulan($iku, string $nama, array $attrs = [])
{
    $path = 'usulan-program-kerja/'.Str::slug($nama).'.pdf';
    Storage::disk('private')->put($path, 'PDF-'.$nama);

    return makeUsulan($iku, ['nama_usulan' => $nama, 'file_kak_pdf' => $path] + $attrs);
}

function dokumentasiLaporan($iku, string $nama, ?string $isi = 'PDF-LAPORAN')
{
    $usulan = makeUsulan($iku, ['status_validasi' => 'menunggu_validasi']);
    $usulan->setujui(userWithRole('validator', ['email' => Str::slug($nama).'@test.local'])->id);
    $laporan = LaporanKegiatan::create(['proker_id' => $usulan->programKerja->id]);

    $attrs = ['nama_dokumen' => $nama, 'status_validasi' => 'menunggu_validasi'];
    if ($isi !== null) {
        $path = 'laporan-kegiatan/'.Str::slug($nama).'.pdf';
        Storage::disk('private')->put($path, $isi);
        $attrs['file_dokumen'] = $path;
    }

    return $laporan->dokumen()->create($attrs);
}

function dokumentasiCapaian($tahun, $iku, string $twKode, string $namaPts, bool $adaFile = true, string $isi = 'PDF-CAPAIAN')
{
    $tw = Triwulan::where('kode', $twKode)->value('id');
    $capaian = makeCapaianKinerja($iku, $tahun, ['triwulan_id' => $tw]);
    $pts = Pts::create(['kode_pts' => Str::slug($namaPts), 'nama_pts' => $namaPts, 'status_pts' => 'aktif']);

    $path = 'capaian-kinerja-hybrid/'.Str::slug($namaPts).'.pdf';
    if ($adaFile) {
        Storage::disk('private')->put($path, $isi);
    }

    return CapaianFasilitasiMutuPts::create([
        'capaian_kinerja_id' => $capaian->id, 'pts_id' => $pts->id,
        'bentuk_fasilitasi' => 'Pelatihan', 'tanggal_kegiatan' => now(),
        'file_bukti_dukung' => $path, 'status_validasi' => 'draft',
    ]);
}

function dokumentasiIkuCapaian($tahun)
{
    return makeIku(makeSasaranKegiatan($tahun, 'Sasaran Capaian'), ['tipe_iku' => 'fasilitasi_mutu_pts', 'deskripsi' => 'IKU Capaian']);
}

// (1) Akses: admin 200, non-admin 403 — halaman & seluruh endpoint file

it('admin 200 dan non-admin 403 pada halaman dan endpoint file', function () {
    $usulan = dokumentasiUsulan($this->iku, 'Usulan Akses');
    $dokLaporan = dokumentasiLaporan($this->iku, 'Dokumen Akses');
    $baris = dokumentasiCapaian($this->tahun, dokumentasiIkuCapaian($this->tahun), 'TW1', 'Univ Akses');

    $urls = [
        route('admin.dokumentasi.index'),
        route('admin.dokumentasi.file.usulan.preview', [$usulan->id, 'kak']),
        route('admin.dokumentasi.file.usulan.unduh', [$usulan->id, 'kak']),
        route('admin.dokumentasi.file.laporan.preview', $dokLaporan->id),
        route('admin.dokumentasi.file.laporan.unduh', $dokLaporan->id),
        route('admin.dokumentasi.file.capaian.preview', ['fasilitasi_mutu_pts', 'utama', $baris->id]),
        route('admin.dokumentasi.file.capaian.unduh', ['fasilitasi_mutu_pts', 'utama', $baris->id]),
    ];

    $admin = userWithRole('admin');
    $timKerja = userWithRole('tim_kerja');

    foreach ($urls as $url) {
        $this->actingAs($admin)->get($url)->assertOk();
        $this->actingAs($timKerja)->get($url)->assertForbidden();
    }
});

// (2) Filter IKU & Tim Kerja

it('filter iku_id dan tim_kerja_id menyaring baris', function () {
    $iku2 = makeIku(makeSasaranKegiatan($this->tahun, 'Sasaran Dua'), ['deskripsi' => 'IKU Dua']);
    $tim = makeTimKerja('Tim Filter');
    $this->iku->timKerja()->attach($tim->id);

    dokumentasiUsulan($this->iku, 'Usulan Milik Iku Satu');
    dokumentasiUsulan($iku2, 'Usulan Milik Iku Dua');

    $admin = userWithRole('admin');
    $base = ['kategori' => 'usulan'];

    $this->actingAs($admin)->get(route('admin.dokumentasi.index', $base + ['iku_id' => $this->iku->id]))
        ->assertOk()->assertSee('Usulan Milik Iku Satu')->assertDontSee('Usulan Milik Iku Dua');

    $this->actingAs($admin)->get(route('admin.dokumentasi.index', $base + ['tim_kerja_id' => $tim->id]))
        ->assertOk()->assertSee('Usulan Milik Iku Satu')->assertDontSee('Usulan Milik Iku Dua');
});

// (3) Preview men-stream binary PDF dari disk private

it('preview men-stream PDF dari disk private, tanpa Content-Disposition', function () {
    $usulan = dokumentasiUsulan($this->iku, 'Usulan Stream');
    $dokLaporan = dokumentasiLaporan($this->iku, 'Dokumen Stream', 'ISI-LAPORAN');
    $baris = dokumentasiCapaian($this->tahun, dokumentasiIkuCapaian($this->tahun), 'TW1', 'Univ Stream', true, 'ISI-CAPAIAN');
    $admin = userWithRole('admin');

    $kasus = [
        [route('admin.dokumentasi.file.usulan.preview', [$usulan->id, 'kak']), 'PDF-Usulan Stream'],
        [route('admin.dokumentasi.file.laporan.preview', $dokLaporan->id), 'ISI-LAPORAN'],
        [route('admin.dokumentasi.file.capaian.preview', ['fasilitasi_mutu_pts', 'utama', $baris->id]), 'ISI-CAPAIAN'],
    ];

    foreach ($kasus as [$url, $isi]) {
        $response = $this->actingAs($admin)->get($url);
        $response->assertOk();
        expect($response->headers->get('Content-Type'))->toStartWith('application/pdf')
            ->and($response->headers->has('Content-Disposition'))->toBeFalse()
            ->and(dokumentasiBody($response))->toBe($isi);
    }
});

it('RAB Excel hanya bisa diunduh, pratinjau 404', function () {
    Storage::disk('private')->put('usulan-program-kerja/rab.xlsx', 'XLSX');
    $usulan = makeUsulan($this->iku, ['file_rab_excel' => 'usulan-program-kerja/rab.xlsx']);
    $admin = userWithRole('admin');

    $this->actingAs($admin)->get(route('admin.dokumentasi.file.usulan.preview', [$usulan->id, 'rab-excel']))->assertNotFound();

    $unduh = $this->actingAs($admin)->get(route('admin.dokumentasi.file.usulan.unduh', [$usulan->id, 'rab-excel']));
    $unduh->assertOk();
    expect($unduh->headers->get('Content-Disposition'))->toContain('attachment');
});

it('endpoint capaian menolak field di luar whitelist', function () {
    $baris = dokumentasiCapaian($this->tahun, dokumentasiIkuCapaian($this->tahun), 'TW1', 'Univ Whitelist');

    $this->actingAs(userWithRole('admin'))
        ->get(route('admin.dokumentasi.file.capaian.preview', ['fasilitasi_mutu_pts', 'utama', $baris->id, 'field' => 'catatan_revisi']))
        ->assertNotFound();
});

// (4) Dokumen tanpa file tidak muncul

it('dokumen tanpa file (null atau hilang dari disk) tidak muncul', function () {
    dokumentasiUsulan($this->iku, 'Usulan File Ada');
    $hilang = makeUsulan($this->iku, ['nama_usulan' => 'Usulan File Hilang', 'file_kak_pdf' => 'usulan-program-kerja/tidak-ada.pdf']);

    dokumentasiLaporan($this->iku, 'Dokumen Laporan Ada');
    dokumentasiLaporan($this->iku, 'Dokumen Laporan Kosong', null);

    $ikuCap = dokumentasiIkuCapaian($this->tahun);
    dokumentasiCapaian($this->tahun, $ikuCap, 'TW1', 'Univ Ada');
    dokumentasiCapaian($this->tahun, $ikuCap, 'TW2', 'Univ Menggantung', false);

    $this->actingAs(userWithRole('admin'))->get(route('admin.dokumentasi.index'))
        ->assertOk()
        ->assertSee('Usulan File Ada')
        ->assertDontSee('Usulan File Hilang')
        ->assertSee('Dokumen Laporan Ada')
        ->assertDontSee('Dokumen Laporan Kosong')
        ->assertSee('Univ Ada')
        ->assertDontSee('Univ Menggantung');
});

// (5) Filter triwulan untuk Capaian

it('filter triwulan=TW2 hanya menampilkan capaian TW2', function () {
    $ikuCap = dokumentasiIkuCapaian($this->tahun);
    dokumentasiCapaian($this->tahun, $ikuCap, 'TW1', 'Univ Triwulan Satu');
    dokumentasiCapaian($this->tahun, $ikuCap, 'TW2', 'Univ Triwulan Dua');

    $this->actingAs(userWithRole('admin'))
        ->get(route('admin.dokumentasi.index', ['kategori' => 'capaian', 'triwulan' => 'TW2']))
        ->assertOk()->assertSee('Univ Triwulan Dua')->assertDontSee('Univ Triwulan Satu');
});

// (6) Triwulan diturunkan dari bulan kegiatan (rentang tanggal)

it('proker bulan Mar-Apr muncul di TW1 dan TW2, tidak di TW3', function () {
    $usulan = dokumentasiUsulan($this->iku, 'Usulan Lintas Triwulan');
    DetailKegiatan::create([
        'usulan_program_kerja_id' => $usulan->id, 'nama_detail' => 'Detail', 'tempat_pelaksanaan' => 'Kantor',
        'bentuk_kegiatan' => 'Luring', 'tanggal_mulai' => '2026-03-10', 'tanggal_selesai' => '2026-04-10', 'anggaran' => 1000,
    ]);
    $admin = userWithRole('admin');

    foreach (['TW1', 'TW2'] as $tw) {
        $this->actingAs($admin)->get(route('admin.dokumentasi.index', ['kategori' => 'usulan', 'triwulan' => $tw]))
            ->assertOk()->assertSee('Usulan Lintas Triwulan');
    }

    $this->actingAs($admin)->get(route('admin.dokumentasi.index', ['kategori' => 'usulan', 'triwulan' => 'TW3']))
        ->assertOk()->assertDontSee('Usulan Lintas Triwulan');
});

// (7) Usulan tanpa Detail Kegiatan

it('usulan tanpa detail kegiatan tidak muncul saat filter triwulan aktif, tapi muncul tanpa filter', function () {
    dokumentasiUsulan($this->iku, 'Usulan Tanpa Detail');
    $admin = userWithRole('admin');

    $this->actingAs($admin)->get(route('admin.dokumentasi.index', ['kategori' => 'usulan', 'triwulan' => 'TW1']))
        ->assertOk()->assertDontSee('Usulan Tanpa Detail');

    $this->actingAs($admin)->get(route('admin.dokumentasi.index', ['kategori' => 'usulan']))
        ->assertOk()->assertSee('Usulan Tanpa Detail');
});

// (8) Nilai tidak valid diabaikan

it('nilai triwulan dan kategori tidak valid diabaikan tanpa error', function () {
    dokumentasiUsulan($this->iku, 'Usulan Param Salah');

    $this->actingAs(userWithRole('admin'))
        ->get(route('admin.dokumentasi.index', ['kategori' => 'ngawur', 'triwulan' => 'TW9']))
        ->assertOk()->assertSee('Usulan Param Salah');
});