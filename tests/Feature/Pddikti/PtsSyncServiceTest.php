<?php

use App\Models\Pts;
use App\Models\PtsAkreditasi;
use App\Services\Pddikti\PddiktiApiException;
use App\Services\Pddikti\PtsDataSource;
use App\Services\PtsSyncService;

beforeEach(fn () => config(['pddikti.driver' => 'lldikti12']));

function syncPts(array $baris): array
{
    return (new PtsSyncService(sumberPalsu($baris)))->sinkronkanPts();
}

function syncAkreditasi(array $baris): array
{
    return (new PtsSyncService(sumberPalsu([], $baris)))->sinkronkanAkreditasi();
}

// --- Master PTS ---

it('membuat PTS baru: sumber_data = driver aktif, status dan akreditasi dipetakan', function () {
    $r = syncPts([apiPts()]);

    $pts = Pts::where('kode_pts', '121002')->first();
    expect($r['dibuat'])->toBe(1)
        ->and($pts->status_pts)->toBe('aktif')
        ->and($pts->akreditasi_pts)->toBe('terakreditasi') // "Baik Sekali"
        ->and($pts->provinsi)->toBe('Maluku')
        ->and($pts->sumber_data)->toBe('lldikti12')
        ->and($pts->terakhir_sinkron_at)->not->toBeNull();
});

it('menyaring provinsi persis (tanpa beda huruf/spasi); "Maluku" tidak menangkap provinsi lain', function () {
    $r = syncPts([
        apiPts(['kode_pts' => 'A1', 'provinsi' => 'Maluku']),
        apiPts(['kode_pts' => 'A2', 'provinsi' => '  maluku   utara ']),
        apiPts(['kode_pts' => 'A3', 'provinsi' => 'Maluku Barat Daya']),
        apiPts(['kode_pts' => 'A4', 'provinsi' => 'Prov. Maluku Utara']),
        apiPts(['kode_pts' => 'A5', 'provinsi' => 'Sulawesi Selatan']),
        apiPts(['kode_pts' => 'A6', 'provinsi' => null]),
    ]);

    expect(Pts::orderBy('kode_pts')->pluck('kode_pts')->all())->toBe(['A1', 'A2'])
        ->and($r['dilewati']['bukan_wilayah'])->toBe(4);
});

it('melewati baris tidak valid: tanpa kode, kode > 20 karakter, tanpa nama', function () {
    $r = syncPts([
        apiPts(['kode_pts' => null]),
        apiPts(['kode_pts' => str_repeat('9', 21)]),
        apiPts(['kode_pts' => 'B3', 'nama_pts' => null]),
        apiPts(['kode_pts' => 'B4']),
    ]);

    expect($r['dibuat'])->toBe(1)
        ->and($r['dilewati']['tidak_valid'])->toBe(3)
        ->and(Pts::count())->toBe(1);
});

it('menggabungkan duplikat kode_pts dan menghitungnya', function () {
    $r = syncPts([apiPts(['kode_pts' => 'C0']), apiPts(['kode_pts' => 'C0'])]);

    expect($r['duplikat_digabung'])->toBe(1)->and(Pts::count())->toBe(1);
});

it('nilai tak dikenal dari API tidak menimpa nilai lokal dan masuk peringatan', function () {
    makePts('C1', ['status_pts' => 'pembinaan', 'sumber_data' => 'lldikti12']);

    $r = syncPts([apiPts(['kode_pts' => 'C1', 'status' => 'Entah Apa', 'peringkat_akreditasi' => 'Zzz'])]);

    $pts = Pts::where('kode_pts', 'C1')->first();
    expect($pts->status_pts)->toBe('pembinaan')
        ->and($pts->akreditasi_pts)->toBeNull()
        ->and($r['peringatan_total'])->toBe(2);
});

it('akreditasi kosong atau SK kedaluwarsa dipetakan tidak_terakreditasi', function () {
    syncPts([
        apiPts(['kode_pts' => 'G1', 'peringkat_akreditasi' => null, 'status_akreditasi' => null]),
        apiPts(['kode_pts' => 'G2', 'peringkat_akreditasi' => 'Baik', 'status_akreditasi' => 'Kedaluwarsa']),
        apiPts(['kode_pts' => 'G3', 'peringkat_akreditasi' => 'Unggul']),
    ]);

    expect(Pts::orderBy('kode_pts')->pluck('akreditasi_pts', 'kode_pts')->all())
        ->toBe(['G1' => 'tidak_terakreditasi', 'G2' => 'tidak_terakreditasi', 'G3' => 'unggul']);
});

it('menjalankan dua kali tidak mengubah data dan tidak menghapus baris', function () {
    $service = new PtsSyncService(sumberPalsu([apiPts(['kode_pts' => 'D1']), apiPts(['kode_pts' => 'D2'])]));
    $ambil = fn () => Pts::orderBy('kode_pts')
        ->get(['kode_pts', 'nama_pts', 'provinsi', 'status_pts', 'akreditasi_pts', 'sumber_data', 'updated_at'])->toArray();

    $service->sinkronkanPts();
    $sebelum = $ambil();
    $r = $service->sinkronkanPts();

    expect($r['tidak_berubah'])->toBe(2)
        ->and($r['dibuat'])->toBe(0)
        ->and($r['diperbarui'])->toBe(0)
        ->and($ambil())->toBe($sebelum)
        ->and(Pts::count())->toBe(2);
});

it('API menang: baris manual ditimpa, sumber_data jadi driver aktif, dan dihitung di ringkasan', function () {
    makePts('E1', ['nama_pts' => 'Nama Koreksi Admin', 'sumber_data' => 'manual']);

    $r = syncPts([apiPts(['kode_pts' => 'E1', 'nama_pts' => 'Nama Resmi'])]);

    $pts = Pts::where('kode_pts', 'E1')->first();
    expect($pts->nama_pts)->toBe('Nama Resmi')
        ->and($pts->sumber_data)->toBe('lldikti12')
        ->and($r['manual_ditimpa'])->toBe(1)
        ->and($r['diperbarui'])->toBe(1);
});

it('melaporkan PTS lokal bersumber API yang hilang dari respons tanpa mengubah atau menghapusnya', function () {
    makePts('F1', ['sumber_data' => 'lldikti12']);
    makePts('F2', ['sumber_data' => 'manual']);

    $r = syncPts([apiPts(['kode_pts' => 'F3'])]);

    expect($r['tidak_muncul'])->toBe(['F1'])
        ->and(Pts::where('kode_pts', 'F1')->value('status_pts'))->toBe('aktif')
        ->and(Pts::count())->toBe(3);
});

it('respons kosong dianggap galat, bukan "semua PTS hilang"', function () {
    makePts('Z1', ['sumber_data' => 'lldikti12']);

    expect(fn () => syncPts([]))->toThrow(PddiktiApiException::class, 'kosong');
    expect(Pts::count())->toBe(1);
});

it('galat dari sumber data tidak menulis apa pun', function () {
    $sumber = new class implements PtsDataSource {
        public function ambilSemuaPts(): array
        {
            throw new PddiktiApiException('Halaman 2 gagal.');
        }

        public function ambilSemuaAkreditasi(): array
        {
            throw new PddiktiApiException('Halaman 2 gagal.');
        }
    };

    expect(fn () => (new PtsSyncService($sumber))->sinkronkanPts())->toThrow(PddiktiApiException::class);
    expect(Pts::count())->toBe(0);
});

// --- Akreditasi ---

it('akreditasi: hanya status Aktif, peringkat diterima, dan PTS lokal yang disimpan; peringkat asli utuh', function () {
    $h1 = makePts('H1');
    makePts('H2');
    makePts('H3');

    $r = syncAkreditasi([
        apiAkreditasi(['kode_pts' => 'H1']),
        apiAkreditasi(['kode_pts' => 'H2', 'status' => 'Kedaluwarsa']),
        apiAkreditasi(['kode_pts' => 'H3', 'peringkat' => 'Belum Terakreditasi']),
        apiAkreditasi(['kode_pts' => 'ZZZ']),
        apiAkreditasi(['kode_pts' => null]),
    ]);

    expect(PtsAkreditasi::count())->toBe(1)
        ->and(PtsAkreditasi::where('pts_id', $h1->id)->value('peringkat'))->toBe('Baik Sekali')
        ->and($r['dibuat'])->toBe(1)
        ->and($r['dilewati'])->toBe(['tidak_valid' => 1, 'status_bukan_aktif' => 1, 'peringkat_ditolak' => 1, 'tanpa_pts_lokal' => 1])
        ->and($r['peringatan_total'])->toBe(0)
        ->and(Pts::count())->toBe(3); // tidak pernah membuat PTS baru
});

it('akreditasi: dua SK aktif untuk satu PTS, yang tgl_sk-nya terbaru dipilih', function () {
    $pts = makePts('I1');

    $r = syncAkreditasi([
        apiAkreditasi(['kode_pts' => 'I1', 'no_sk' => 'SK/LAMA', 'tgl_sk' => '2019-01-01', 'tgl_akhir' => '2024-01-01']),
        apiAkreditasi(['kode_pts' => 'I1', 'no_sk' => 'SK/BARU', 'tgl_sk' => '2023-01-01', 'tgl_akhir' => '2028-01-01']),
    ]);

    expect(PtsAkreditasi::count())->toBe(1)
        ->and(PtsAkreditasi::where('pts_id', $pts->id)->value('no_sk'))->toBe('SK/BARU')
        ->and($r['duplikat_dipilih'])->toBe(1);
});

it('akreditasi: selisih dengan pts.akreditasi_pts dilaporkan sebagai peringatan, tidak ditimpa', function () {
    makePts('J1', ['akreditasi_pts' => 'unggul']);

    $r = syncAkreditasi([apiAkreditasi(['kode_pts' => 'J1', 'peringkat' => 'Baik'])]);

    expect($r['peringatan_total'])->toBe(1)
        ->and(Pts::where('kode_pts', 'J1')->value('akreditasi_pts'))->toBe('unggul');
});

it('akreditasi: nilai kosong dari API tidak menghapus nilai lokal, dan idempoten', function () {
    $pts = makePts('K1');

    syncAkreditasi([apiAkreditasi(['kode_pts' => 'K1', 'no_sk' => 'SK/1'])]);
    $r = syncAkreditasi([apiAkreditasi(['kode_pts' => 'K1', 'no_sk' => null])]);

    expect(PtsAkreditasi::where('pts_id', $pts->id)->value('no_sk'))->toBe('SK/1')
        ->and($r['tidak_berubah'])->toBe(1)
        ->and($r['diperbarui'])->toBe(0);
});

it('memetakan "Terakreditasi" dan "Belum Terakreditasi" apa pun huruf dan spasinya, tanpa peringatan', function () {
    $r = syncPts([
        apiPts(['kode_pts' => 'T1', 'peringkat_akreditasi' => 'Terakreditasi']),
        apiPts(['kode_pts' => 'T2', 'peringkat_akreditasi' => '  BELUM   TERAKREDITASI ']),
    ]);

    expect(Pts::orderBy('kode_pts')->pluck('akreditasi_pts', 'kode_pts')->all())
        ->toBe(['T1' => 'terakreditasi', 'T2' => 'tidak_terakreditasi'])
        ->and($r['peringatan_total'])->toBe(0);
});

it('akreditasi: peringkat "Terakreditasi" disimpan ke pts_akreditasi, "Belum Terakreditasi" tidak', function () {
    $ada = makePts('122001');
    makePts('123076');

    $r = syncAkreditasi([
        apiAkreditasi(['kode_pts' => '122001', 'peringkat' => 'Terakreditasi', 'tgl_akhir' => '2031-01-29']),
        apiAkreditasi(['kode_pts' => '123076', 'peringkat' => 'Belum Terakreditasi']),
    ]);

    expect(PtsAkreditasi::count())->toBe(1)
        ->and(PtsAkreditasi::where('pts_id', $ada->id)->value('peringkat'))->toBe('Terakreditasi')
        ->and(PtsAkreditasi::where('pts_id', $ada->id)->first()->tgl_akhir->toDateString())->toBe('2031-01-29')
        ->and($r['dilewati']['peringkat_ditolak'])->toBe(1)
        ->and($r['peringatan_total'])->toBe(0);
});

// Pagar untuk kesalahan seperti ini: key pemetaan harus sudah huruf kecil dan rapi,
// karena yang dinormalisasi hanya nilai dari API, bukan key di config.
it('semua key pemetaan config sudah huruf kecil dan tanpa spasi berlebih', function () {
    foreach (config('pddikti.pemetaan') as $nama => $peta) {
        foreach (array_keys($peta) as $key) {
            expect((string) $key)->toBe(mb_strtolower(trim(preg_replace('/\s+/u', ' ', (string) $key))), "key \"{$key}\" di pemetaan.{$nama}");
        }
    }
});

it('PTS Non Aktif disimpan sebagai non_aktif; "rencana penyatuan" + SK kedaluwarsa jadi tidak_terakreditasi tanpa peringatan', function () {
    $r = syncPts([apiPts([
        'kode_pts' => '123032', 'status' => 'Non Aktif',
        'peringkat_akreditasi' => 'rencana penyatuan', 'status_akreditasi' => 'Kedaluwarsa',
    ])]);

    $pts = Pts::where('kode_pts', '123032')->first();
    expect($pts->status_pts)->toBe('non_aktif')
        ->and($pts->akreditasi_pts)->toBe('tidak_terakreditasi')
        ->and($pts->sumber_data)->toBe('lldikti12')
        ->and($r['peringatan_total'])->toBe(0);
});

it('PTS lokal aktif yang kini Non Aktif di API diperbarui, bukan dilaporkan hilang', function () {
    makePts('123040', ['sumber_data' => 'lldikti12']);

    $r = syncPts([apiPts(['kode_pts' => '123040', 'status' => 'Non Aktif'])]);

    expect(Pts::where('kode_pts', '123040')->value('status_pts'))->toBe('non_aktif')
        ->and($r['diperbarui'])->toBe(1)
        ->and($r['tidak_muncul'])->toBe([]);
});

it('akreditasi: SK Kedaluwarsa tanpa nomor milik PTS Non Aktif dilewati sebagai status_bukan_aktif, tanpa peringatan', function () {
    makePts('123032', ['status_pts' => 'non_aktif']);

    $r = syncAkreditasi([apiAkreditasi([
        'kode_pts' => '123032', 'peringkat' => 'rencana penyatuan',
        'no_sk' => null, 'tgl_sk' => null, 'tgl_akhir' => null, 'status' => 'Kedaluwarsa',
    ])]);

    expect(PtsAkreditasi::count())->toBe(0)
        ->and($r['dilewati']['status_bukan_aktif'])->toBe(1)
        ->and($r['peringatan_total'])->toBe(0);
});