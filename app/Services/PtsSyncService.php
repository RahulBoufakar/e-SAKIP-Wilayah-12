<?php

namespace App\Services;

use App\Models\Pts;
use App\Models\PtsAkreditasi;
use App\Services\Pddikti\PddiktiApiException;
use App\Services\Pddikti\PtsDataSource;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * TDD Sinkronisasi PTS §2 & §5. Seluruh aturan bisnis (penyaringan, pemetaan,
 * "API selalu menang", null tidak menimpa) ada di sini, bukan di driver.
 * Semua data diambil ke memori dulu; penulisan baru terjadi setelah itu, dalam
 * satu transaksi, jadi galat di tengah jalan tidak meninggalkan data parsial.
 */
class PtsSyncService
{
    private const KOLOM_PTS = ['nama_pts', 'provinsi', 'status_pts', 'akreditasi_pts'];
    private const KOLOM_AKREDITASI = ['peringkat', 'no_sk', 'tgl_sk', 'tgl_akhir', 'status_sk'];
    private const MAKS_PERINGATAN = 50;
    private const MAKS_DAFTAR = 200;
    private const POTONGAN = 200;

    private array $peringatan = [];
    private int $peringatanTotal = 0;

    public function __construct(private PtsDataSource $sumber)
    {
    }

    /** Sinkronisasi master PTS. Mengembalikan ringkasan untuk disimpan di log. */
    public function sinkronkanPts(): array
    {
        $this->resetPeringatan();

        $respons = $this->sumber->ambilSemuaPts();
        if ($respons === []) {
            throw new PddiktiApiException('API mengembalikan daftar PTS kosong. Sinkronisasi dibatalkan, tidak ada data yang diubah.');
        }

        $ringkasan = [
            'dibuat' => 0, 'diperbarui' => 0, 'tidak_berubah' => 0, 'manual_ditimpa' => 0,
            'dilewati' => ['tidak_valid' => 0, 'bukan_wilayah' => 0],
            'duplikat_digabung' => 0,
        ];
        $masuk = $this->saringPts($respons, $ringkasan);

        $lokal = Pts::all()->keyBy('kode_pts');
        $driver = config('pddikti.driver');
        $sekarang = now()->toDateTimeString();
        $tulis = [];

        foreach ($masuk as $kode => $baris) {
            $kode = (string) $kode; // kunci array PHP bisa berubah jadi int untuk kode numerik ("121002")
            $ada = $lokal->get($kode);

            $awal = $ada ? $this->kolom($ada, self::KOLOM_PTS) : array_fill_keys(self::KOLOM_PTS, null);
            $nilai = array_replace($awal, array_filter($baris, fn ($v) => $v !== null)); // null dari API tidak menimpa

            if (! $ada) {
                $ringkasan['dibuat']++;
                $diubah = true;
            } elseif ($nilai !== $awal) {
                $ringkasan['diperbarui']++;
                if ($ada->sumber_data === 'manual') {
                    $ringkasan['manual_ditimpa']++;
                }
                $diubah = true;
            } elseif ($ada->sumber_data !== $driver) {
                $ringkasan['diperbarui']++; // data sama, tetapi sumbernya beralih ke driver aktif
                $diubah = true;
            } else {
                $ringkasan['tidak_berubah']++;
                $diubah = false;
            }

            $tulis[] = ['kode_pts' => $kode] + $nilai + [
                'sumber_data' => $driver,
                'terakhir_sinkron_at' => $sekarang,
                'created_at' => $sekarang,
                'updated_at' => $diubah ? $sekarang : $ada->updated_at?->toDateTimeString(),
            ];
        }

        DB::transaction(function () use ($tulis) {
            foreach (array_chunk($tulis, self::POTONGAN) as $potong) {
                DB::table('pts')->upsert($potong, ['kode_pts'], [...self::KOLOM_PTS, 'sumber_data', 'terakhir_sinkron_at', 'updated_at']);
            }
        });

        // Tidak pernah menghapus/mengubah baris yang hilang dari API, hanya melaporkan.
        $hilang = $lokal
            ->filter(fn ($p) => $p->sumber_data !== 'manual' && ! isset($masuk[$p->kode_pts]))
            ->keys()->map(fn ($k) => (string) $k)->values()->all();
        $ringkasan['tidak_muncul_total'] = count($hilang);
        $ringkasan['tidak_muncul'] = array_slice($hilang, 0, self::MAKS_DAFTAR);

        return $this->selesai($ringkasan);
    }

    /** Sinkronisasi snapshot akreditasi (tabel pts_akreditasi). Tidak pernah membuat PTS baru. */
    public function sinkronkanAkreditasi(): array
    {
        $this->resetPeringatan();

        $respons = $this->sumber->ambilSemuaAkreditasi();
        if ($respons === []) {
            throw new PddiktiApiException('API mengembalikan data akreditasi kosong. Sinkronisasi dibatalkan, tidak ada data yang diubah.');
        }

        $ringkasan = [
            'dibuat' => 0, 'diperbarui' => 0, 'tidak_berubah' => 0,
            'dilewati' => ['tidak_valid' => 0, 'status_bukan_aktif' => 0, 'peringkat_ditolak' => 0, 'tanpa_pts_lokal' => 0],
            'duplikat_dipilih' => 0,
        ];

        $ptsLokal = Pts::get(['id', 'kode_pts', 'akreditasi_pts'])->keyBy('kode_pts');
        $statusDiambil = $this->norm(config('pddikti.status_sk_diambil'));
        $diterima = []; // pts_id => ['pts', 'kategori', 'baris']

        foreach ($respons as $r) {
            $kode = $r['kode_pts'];
            if ($kode === null) {
                $ringkasan['dilewati']['tidak_valid']++;
                continue;
            }
            if ($this->norm($r['status']) !== $statusDiambil) {
                $ringkasan['dilewati']['status_bukan_aktif']++;
                continue;
            }

            $kategori = $this->petakanAkreditasi($r['peringkat'], $r['status'], $kode);
            if (! in_array($kategori, ['unggul', 'terakreditasi'], true)) {
                $ringkasan['dilewati']['peringkat_ditolak']++;
                continue;
            }

            $pts = $ptsLokal->get($kode);
            if (! $pts) {
                $ringkasan['dilewati']['tanpa_pts_lokal']++;
                continue;
            }

            $baris = [
                'peringkat' => $this->potong($r['peringkat'], 50), // peringkat asli API disimpan apa adanya
                'no_sk' => $this->potong($r['no_sk'], 255),
                'tgl_sk' => $this->tanggal($r['tgl_sk'], $kode),
                'tgl_akhir' => $this->tanggal($r['tgl_akhir'], $kode),
                'status_sk' => $this->potong($r['status'], 30),
            ];

            if (isset($diterima[$pts->id])) {
                $ringkasan['duplikat_dipilih']++;
                // Sama-sama aktif: yang tgl_sk-nya terbaru menang; seri = yang lebih dulu.
                if (($diterima[$pts->id]['baris']['tgl_sk'] ?? '') >= ($baris['tgl_sk'] ?? '')) {
                    continue;
                }
            }
            $diterima[$pts->id] = compact('pts', 'kategori', 'baris');
        }

        $lokal = PtsAkreditasi::all()->keyBy('pts_id');
        $sekarang = now()->toDateTimeString();
        $tulis = [];

        foreach ($diterima as $item) {
            ['pts' => $pts, 'kategori' => $kategori, 'baris' => $baris] = $item;

            // pts.akreditasi_pts hanya ditulis sinkronisasi master PTS; selisih cukup dilaporkan.
            if ($pts->akreditasi_pts !== null && $pts->akreditasi_pts !== $kategori) {
                $this->tambahPeringatan("PTS {$pts->kode_pts}: akreditasi di data PTS ({$pts->akreditasi_pts}) berbeda dengan data akreditasi API ({$kategori})");
            }

            $ada = $lokal->get($pts->id);
            $awal = $ada ? $this->kolom($ada, self::KOLOM_AKREDITASI) : array_fill_keys(self::KOLOM_AKREDITASI, null);
            $nilai = array_replace($awal, array_filter($baris, fn ($v) => $v !== null));

            if (! $ada) {
                $ringkasan['dibuat']++;
                $diubah = true;
            } elseif ($nilai !== $awal) {
                $ringkasan['diperbarui']++;
                $diubah = true;
            } else {
                $ringkasan['tidak_berubah']++;
                $diubah = false;
            }

            $tulis[] = ['pts_id' => $pts->id] + $nilai + [
                'terakhir_sinkron_at' => $sekarang,
                'created_at' => $sekarang,
                'updated_at' => $diubah ? $sekarang : $ada->updated_at?->toDateTimeString(),
            ];
        }

        DB::transaction(function () use ($tulis) {
            foreach (array_chunk($tulis, self::POTONGAN) as $potong) {
                DB::table('pts_akreditasi')->upsert($potong, ['pts_id'], [...self::KOLOM_AKREDITASI, 'terakhir_sinkron_at', 'updated_at']);
            }
        });

        return $this->selesai($ringkasan);
    }

    /** Buang baris tidak valid / di luar wilayah, gabungkan duplikat kode_pts, petakan status & akreditasi. */
    private function saringPts(array $respons, array &$ringkasan): array
    {
        $provinsiOk = array_map(fn ($p) => $this->norm($p), config('pddikti.provinsi'));
        $unik = [];

        foreach ($respons as $r) {
            $kode = $r['kode_pts'];
            $nama = $r['nama_pts'];

            if ($kode === null || $nama === null || mb_strlen($kode) > 20) {
                $ringkasan['dilewati']['tidak_valid']++;
                continue;
            }

            // Persis sama (bukan contains): "Maluku" tidak boleh ikut menangkap "Maluku Utara".
            if (! in_array($this->norm($r['provinsi']), $provinsiOk, true)) {
                $ringkasan['dilewati']['bukan_wilayah']++;
                continue;
            }

            $baris = [
                'nama_pts' => $this->potong($nama, 255),
                'provinsi' => $this->potong($r['provinsi'], 100),
                'status_pts' => $this->petakanStatus($r['status'], $kode),
                'akreditasi_pts' => $this->petakanAkreditasi($r['peringkat_akreditasi'], $r['status_akreditasi'], $kode),
            ];

            if (isset($unik[$kode])) {
                $ringkasan['duplikat_digabung']++;
                $baris = array_filter($unik[$kode], fn ($v) => $v !== null) + $baris; // yang lebih dulu menang, null diisi yang berikutnya
            }
            $unik[$kode] = $baris;
        }

        return $unik;
    }

    private function petakanStatus(?string $status, string $kode): ?string
    {
        $hasil = $status === null ? null : (config('pddikti.pemetaan.status_pts')[$this->norm($status)] ?? null);

        if ($hasil === null) {
            $this->tambahPeringatan("PTS {$kode}: status ".($status === null ? 'kosong' : "\"{$this->potong($status, 50)}\" tidak dikenal").', tidak dipetakan');
        }

        return $hasil;
    }

    /** Tanpa peringkat atau SK Kedaluwarsa = tidak_terakreditasi; peringkat di luar daftar = null + peringatan. */
    private function petakanAkreditasi(?string $peringkat, ?string $status, string $kode): ?string
    {
        if ($peringkat === null || $this->norm($status) === 'kedaluwarsa') {
            return 'tidak_terakreditasi';
        }

        $hasil = config('pddikti.pemetaan.akreditasi')[$this->norm($peringkat)] ?? null;
        if ($hasil === null) {
            $this->tambahPeringatan("PTS {$kode}: peringkat akreditasi \"{$this->potong($peringkat, 50)}\" tidak dikenal");
        }

        return $hasil;
    }

    /** Nilai mentah dari DB (tanpa cast) dengan urutan kunci tetap, supaya bisa dibandingkan ketat. */
    private function kolom(Model $model, array $kolom): array
    {
        return array_replace(array_fill_keys($kolom, null), Arr::only($model->getRawOriginal(), $kolom));
    }

    private function tanggal(?string $nilai, string $kode): ?string
    {
        if ($nilai === null) {
            return null;
        }

        try {
            return Carbon::parse($nilai)->toDateString();
        } catch (Throwable) {
            $this->tambahPeringatan("PTS {$kode}: tanggal \"{$this->potong($nilai, 30)}\" tidak valid, diabaikan");

            return null;
        }
    }

    private function potong(?string $nilai, int $maks): ?string
    {
        return $nilai === null ? null : mb_substr($nilai, 0, $maks);
    }

    /** Huruf kecil, spasi dirapikan: dipakai untuk semua pencocokan nilai dari API. */
    private function norm(?string $nilai): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', (string) $nilai) ?? ''));
    }

    private function resetPeringatan(): void
    {
        $this->peringatan = [];
        $this->peringatanTotal = 0;
    }

    private function tambahPeringatan(string $pesan): void
    {
        $this->peringatanTotal++;
        if (count($this->peringatan) < self::MAKS_PERINGATAN) {
            $this->peringatan[] = $pesan;
        }
    }

    private function selesai(array $ringkasan): array
    {
        return $ringkasan + ['peringatan' => $this->peringatan, 'peringatan_total' => $this->peringatanTotal];
    }
}