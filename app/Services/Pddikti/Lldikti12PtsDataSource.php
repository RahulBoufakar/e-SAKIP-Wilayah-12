<?php

namespace App\Services\Pddikti;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class Lldikti12PtsDataSource implements PtsDataSource
{
    /** @param  array  $config  isi config('pddikti.drivers.lldikti12') */
    public function __construct(private array $config)
    {
    }

    public function ambilSemuaPts(): array
    {
        $baris = [];
        foreach ((array) config('pddikti.status_diambil') as $status) {
            $baris = array_merge($baris, $this->ambilSemuaHalaman($this->config['endpoints']['pts'], [
                'status_pts' => $status,
                'semua' => 0,
            ]));
        }

        return array_map(fn ($r) => [
            'kode_pts' => $this->bersih(data_get($r, 'kode_pts')),
            'nama_pts' => $this->bersih(data_get($r, 'nama_pts')),
            'provinsi' => $this->bersih(data_get($r, 'provinsi')),
            'status' => $this->bersih(data_get($r, 'status_pts')),
            'peringkat_akreditasi' => $this->bersih(data_get($r, 'akreditasi.peringkat')),
            'status_akreditasi' => $this->bersih(data_get($r, 'akreditasi.status')),
        ], $baris);
    }

    public function ambilSemuaAkreditasi(): array
    {
        $baris = $this->ambilSemuaHalaman($this->config['endpoints']['akreditasi'], []);

        return array_map(fn ($r) => [
            'kode_pts' => $this->bersih(data_get($r, 'kode_pts')),
            'peringkat' => $this->bersih(data_get($r, 'peringkat')),
            'no_sk' => $this->bersih(data_get($r, 'no_sk')),
            'tgl_sk' => $this->bersih(data_get($r, 'tgl_sk')),
            'tgl_akhir' => $this->bersih(data_get($r, 'tgl_akhir')),
            'status' => $this->bersih(data_get($r, 'status')),
        ], $baris);
    }

    /** Ulangi dari page=1 sampai meta.last_page. Satu halaman gagal => seluruh proses gagal. */
    private function ambilSemuaHalaman(string $endpoint, array $params): array
    {
        $semua = [];
        $halaman = 1;
        $terakhir = 1;

        do {
            $json = $this->ambilHalaman($endpoint, $params + [
                'per_page' => $this->config['per_page'],
                'page' => $halaman,
            ]);

            $terakhir = (int) $json['meta']['last_page'];
            $semua = array_merge($semua, $json['data']);
            $halaman++;
        } while ($halaman <= $terakhir);

        return $semua;
    }

    private function ambilHalaman(string $endpoint, array $query): array
    {
        $klien = $this->klien();
        $ulang = 0;
        $tunggu429 = 0;

        while (true) {
            try {
                $response = $klien->get($endpoint, $query);
            } catch (ConnectionException) {
                $this->jedaAtauGagal(++$ulang, "Koneksi ke API LLDikti 12 gagal pada endpoint {$endpoint}.");
                continue;
            }

            $status = $response->status();

            if ($status === 429) {
                $tunggu = max(1, (int) $response->header('Retry-After'));
                $tunggu429 += $tunggu;
                if ($tunggu429 > (int) $this->config['maks_tunggu_429']) {
                    throw new PddiktiApiException('API LLDikti 12 membatasi permintaan (HTTP 429) terlalu lama. Coba lagi nanti.');
                }
                $this->tidur($tunggu);
                continue;
            }

            if (in_array($status, [401, 403], true)) {
                throw new PddiktiApiException("API key ditolak oleh LLDikti 12 (HTTP {$status}).");
            }

            if ($status === 404) {
                throw new PddiktiApiException("Endpoint {$endpoint} tidak ditemukan di API LLDikti 12 (HTTP 404).");
            }

            if ($response->serverError()) {
                $this->jedaAtauGagal(++$ulang, "API LLDikti 12 error (HTTP {$status}) pada endpoint {$endpoint}.");
                continue;
            }

            if ($response->failed()) {
                throw new PddiktiApiException("API LLDikti 12 menolak permintaan (HTTP {$status}) pada endpoint {$endpoint}.");
            }

            $json = $response->json();
            if (! is_array($json) || ! is_array($json['data'] ?? null) || ! isset($json['meta']['last_page'])) {
                throw new PddiktiApiException("Struktur respons endpoint {$endpoint} tidak dikenal.");
            }

            $this->hormatiBatasLaju($response);

            return $json;
        }
    }

    /** Ulang 3x dengan jeda bertambah; lewat dari itu gagal. */
    private function jedaAtauGagal(int $ke, string $pesanGagal): void
    {
        $maks = (int) $this->config['retry']['percobaan'];
        if ($ke > $maks) {
            throw new PddiktiApiException($pesanGagal.' Sudah dicoba ulang '.$maks.' kali.');
        }

        $this->tidur((int) $this->config['retry']['jeda_detik'] * $ke);
    }

    /** X-RateLimit-Remaining habis => jeda singkat sebelum halaman berikutnya. */
    private function hormatiBatasLaju(Response $response): void
    {
        if ($response->header('X-RateLimit-Remaining') === '0') {
            $this->tidur(1);
        }
    }

    private function klien(): PendingRequest
    {
        if (blank($this->config['base_url'] ?? null) || blank($this->config['api_key'] ?? null)) {
            throw new PddiktiApiException('Konfigurasi API LLDikti 12 belum lengkap (LLDIKTI12_BASE_URL / LLDIKTI12_API_KEY).');
        }

        return Http::baseUrl($this->config['base_url'])
            ->withHeaders(['x-api-key' => $this->config['api_key']])
            ->acceptJson()
            ->timeout((int) $this->config['timeout']);
    }

    private function bersih(mixed $nilai): ?string
    {
        if (! is_scalar($nilai)) {
            return null;
        }

        $nilai = trim((string) $nilai);

        return $nilai === '' ? null : $nilai;
    }

    /** protected supaya tes tidak benar-benar menunggu. */
    protected function tidur(int $detik): void
    {
        sleep($detik);
    }
}