<?php

use App\Services\Pddikti\Lldikti12PtsDataSource;
use App\Services\Pddikti\PddiktiApiException;
use App\Services\Pddikti\PddiktiPtsDataSource;
use App\Services\Pddikti\PtsDataSource;
use Illuminate\Support\Facades\Http;

// Fixture di bawah masih inline (bentuk dari TDD §1). Ganti dengan fixture respons asli setelah Tahap 0.

beforeEach(function () {
    config([
        'pddikti.drivers.lldikti12.base_url' => 'https://api.test/v1',
        'pddikti.drivers.lldikti12.api_key' => 'kunci-uji',
        'pddikti.status_diambil' => ['Aktif'], // tes lama = satu status; tes multi-status di bawah menimpanya
    ]);
});

function driverUji(): Lldikti12PtsDataSource
{
    return new class(config('pddikti.drivers.lldikti12')) extends Lldikti12PtsDataSource {
        public array $jedaTercatat = [];

        protected function tidur(int $detik): void
        {
            $this->jedaTercatat[] = $detik;
        }
    };
}

function halamanPts(array $baris, int $page, int $last): array
{
    return ['data' => $baris, 'meta' => ['page' => $page, 'per_page' => 100, 'total' => 2, 'last_page' => $last]];
}

it('mengambil semua halaman sampai last_page dan menormalisasi baris PTS', function () {
    Http::fake([
        'api.test/v1/pts*' => Http::sequence()
            ->push(halamanPts([[
                'kode_pts' => '001001', 'nama_pts' => '  PTS A ', 'provinsi' => 'Maluku', 'status_pts' => 'Aktif',
                'akreditasi' => ['peringkat' => 'Unggul', 'no_sk' => 'SK/1', 'status' => 'Aktif'],
            ]], 1, 2))
            ->push(halamanPts([[
                'kode_pts' => '001002', 'nama_pts' => 'PTS B', 'provinsi' => 'Maluku Utara', 'status_pts' => 'Aktif', 'akreditasi' => null,
            ]], 2, 2)),
    ]);

    $hasil = driverUji()->ambilSemuaPts();

    expect($hasil)->toHaveCount(2)
        ->and($hasil[0])->toBe([
            'kode_pts' => '001001', 'nama_pts' => 'PTS A', 'provinsi' => 'Maluku', 'status' => 'Aktif',
            'peringkat_akreditasi' => 'Unggul', 'status_akreditasi' => 'Aktif',
        ])
        ->and($hasil[1]['peringkat_akreditasi'])->toBeNull();

    Http::assertSentCount(2);
    Http::assertSent(fn ($r) => $r->hasHeader('x-api-key', 'kunci-uji')
        && str_contains($r->url(), 'status_pts=Aktif')
        && str_contains($r->url(), 'semua=0')
        && str_contains($r->url(), 'per_page=100')
        && str_contains($r->url(), 'page=1'));
    Http::assertSent(fn ($r) => str_contains($r->url(), 'page=2'));
});

it('menormalisasi akreditasi dari endpoint /akreditasi/pts', function () {
    Http::fake(['api.test/v1/akreditasi/pts*' => Http::response(halamanPts([[
        'kode_pts' => '001001', 'nama_nama' => 'diabaikan', 'peringkat' => 'Baik Sekali', 'no_sk' => 'SK/1',
        'tgl_sk' => '2024-01-10', 'tgl_akhir' => '2029-01-10', 'status' => 'Aktif',
    ]], 1, 1))]);

    expect(driverUji()->ambilSemuaAkreditasi())->toBe([[
        'kode_pts' => '001001', 'peringkat' => 'Baik Sekali', 'no_sk' => 'SK/1',
        'tgl_sk' => '2024-01-10', 'tgl_akhir' => '2029-01-10', 'status' => 'Aktif',
    ]]);
});

it('gagal tanpa percobaan ulang saat HTTP 401', function () {
    Http::fake(['*' => Http::response([], 401)]);

    expect(fn () => driverUji()->ambilSemuaPts())->toThrow(PddiktiApiException::class, 'API key ditolak');
    Http::assertSentCount(1);
});

it('menghormati Retry-After saat HTTP 429 lalu berhasil', function () {
    Http::fake(['*' => Http::sequence()
        ->push([], 429, ['Retry-After' => '3'])
        ->push(halamanPts([], 1, 1))]);

    $driver = driverUji();

    expect($driver->ambilSemuaPts())->toBe([])
        ->and($driver->jedaTercatat)->toBe([3]);
});

it('menyerah bila total tunggu 429 melewati batas', function () {
    config(['pddikti.drivers.lldikti12.maks_tunggu_429' => 5]);
    Http::fake(['*' => Http::response([], 429, ['Retry-After' => '10'])]);

    expect(fn () => driverUji()->ambilSemuaPts())->toThrow(PddiktiApiException::class, 'HTTP 429');
});

it('mengulang 3 kali dengan jeda bertambah saat HTTP 5xx lalu gagal', function () {
    Http::fake(['*' => Http::response([], 500)]);
    $driver = driverUji();

    expect(fn () => $driver->ambilSemuaPts())->toThrow(PddiktiApiException::class);

    Http::assertSentCount(4); // 1 percobaan awal + 3 ulang
    expect($driver->jedaTercatat)->toBe([2, 4, 6]);
});

it('gagal bila struktur respons tidak dikenal', function () {
    Http::fake(['*' => Http::response(['foo' => 'bar'])]);

    expect(fn () => driverUji()->ambilSemuaPts())->toThrow(PddiktiApiException::class, 'tidak dikenal');
});

it('gagal bila halaman kedua error, tanpa mengembalikan data parsial', function () {
    Http::fake(['*' => Http::sequence()
        ->push(halamanPts([['kode_pts' => '1', 'nama_pts' => 'A']], 1, 2))
        ->push([], 404)]);

    expect(fn () => driverUji()->ambilSemuaPts())->toThrow(PddiktiApiException::class, '404');
});

it('menolak bila API key atau base URL kosong, tanpa memanggil API', function () {
    config(['pddikti.drivers.lldikti12.api_key' => '']);
    Http::fake();

    expect(fn () => driverUji()->ambilSemuaPts())->toThrow(PddiktiApiException::class, 'belum lengkap');
    Http::assertNothingSent();
});

it('memilih kelas driver sesuai config dan menolak driver tak dikenal', function () {
    config(['pddikti.driver' => 'lldikti12']);
    expect(app(PtsDataSource::class))->toBeInstanceOf(Lldikti12PtsDataSource::class);

    config(['pddikti.driver' => 'pddikti']);
    expect(app(PtsDataSource::class))->toBeInstanceOf(PddiktiPtsDataSource::class);

    config(['pddikti.driver' => 'ngawur']);
    expect(fn () => app(PtsDataSource::class))->toThrow(InvalidArgumentException::class, 'ngawur');
});

it('stub driver PDDikti melempar pengecualian belum tersedia', function () {
    expect(fn () => (new PddiktiPtsDataSource())->ambilSemuaPts())
        ->toThrow(PddiktiApiException::class, 'belum tersedia');
});

it('mengambil PTS untuk setiap status di config dan menggabungkan hasilnya', function () {
    config(['pddikti.status_diambil' => ['Aktif', 'Non Aktif']]);

    Http::fake(function ($request) {
        $nonAktif = str_contains(urldecode($request->url()), 'status_pts=Non Aktif');

        return Http::response(halamanPts([[
            'kode_pts' => $nonAktif ? '123032' : '001001', 'nama_pts' => 'PTS', 'provinsi' => 'Maluku',
            'status_pts' => $nonAktif ? 'Non Aktif' : 'Aktif',
            'akreditasi' => ['peringkat' => 'rencana penyatuan', 'status' => 'Kedaluwarsa'],
        ]], 1, 1));
    });

    $hasil = driverUji()->ambilSemuaPts();

    expect(collect($hasil)->pluck('kode_pts')->all())->toBe(['001001', '123032'])
        ->and(collect($hasil)->pluck('status')->all())->toBe(['Aktif', 'Non Aktif']);
    Http::assertSentCount(2);
    Http::assertSent(fn ($r) => str_contains(urldecode($r->url()), 'status_pts=Non Aktif') && str_contains($r->url(), 'semua=0'));
});