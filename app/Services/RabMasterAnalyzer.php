<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * RAB Generator 05-algoritma-perakit.md §1-2 — deteksi struktur file master.
 *
 * Hanya MEMBACA dan menghasilkan usulan peta (array biasa). Tidak menulis ke
 * database: penyimpanan/konfirmasi admin adalah langkah 6 (08-rencana-implementasi).
 *
 * Hierarki per sheet: RO (kolom A) -> komponen -> sub -> akun -> detail.
 * Setiap baris bertipe RO/komponen/sub/akun memulai satu SEGMEN yang berlanjut
 * sampai baris sebelum segmen berikutnya; baris kosong dan detail ikut segmen
 * di atasnya. Segmen terakhir berakhir di baris detail/akun terakhir.
 *
 * - kategori.baris_awal..akhir = segmen komponen itu sendiri (judul + spasi), BUKAN
 *   seluruh isi komponen (03).
 * - kelompok.baris_awal..akhir = segmen sub sampai akhir akun terakhirnya.
 *
 * Asumsi: kolom A dan B berisi nilai literal (bukan rumus). Nilai numerik
 * (mis. akun 521211 tersimpan sebagai angka) dibaca sebagai teks.
 */
class RabMasterAnalyzer
{
    private const POLA_RO = '/^\d{4}\.[A-Z]{2,3}\.\w+$/';
    private const POLA_KOMPONEN = '/^\d{3}$/';
    private const POLA_SUB = '/^[A-Z]$/';
    private const POLA_AKUN = '/^\d{6}$/';

    /**
     * @return array{sheets: array<int, array>, peringatan: string[]}
     */
    public function analyze(string $path): array
    {
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadEmptyCells(false);
        $spreadsheet = $reader->load($path);

        try {
            return $this->analyzeSpreadsheet($spreadsheet);
        } finally {
            $spreadsheet->disconnectWorksheets();
        }
    }

    /**
     * @return array{sheets: array<int, array>, peringatan: string[]}
     */
    public function analyzeSpreadsheet(Spreadsheet $spreadsheet): array
    {
        $sheets = [];
        $peringatan = [];

        foreach ($spreadsheet->getAllSheets() as $i => $worksheet) {
            $hasil = $this->analisisSheet($worksheet, $i + 1);

            if ($hasil === null) {
                $peringatan[] = "Sheet \"{$worksheet->getTitle()}\" dilewati: kode RO tidak ditemukan di kolom A.";

                continue;
            }

            $sheets[] = $hasil;
        }

        return ['sheets' => $sheets, 'peringatan' => $peringatan];
    }

    /** Null bila sheet tidak punya baris RO (tidak ada jangkar untuk segmen). */
    private function analisisSheet(Worksheet $ws, int $urutan): ?array
    {
        $rows = $this->bacaKolomAB($ws);
        $peringatan = [];

        // Pass 1: tipe tiap baris. Pemindaian struktur dimulai dari RO pertama;
        // semua baris sebelumnya adalah header (tidak diinterpretasi).
        $segmen = []; // [nomor baris => tipe] untuk ro/komponen/sub/akun
        $roBaris = null;
        $detailTerakhir = 0;

        foreach ($rows as $no => [$a, $b]) {
            $tipe = $this->tipeBaris($a, $b);

            if ($roBaris === null) {
                if ($tipe === 'ro') {
                    $roBaris = $no;
                    $segmen[$no] = 'ro';
                }

                continue;
            }

            if ($tipe === 'ro') {
                // Satu RO per sheet. RO tambahan diperlakukan sebagai baris biasa (ikut segmen di atasnya).
                $peringatan[] = "Ada lebih dari satu kode RO pada sheet (baris {$roBaris} dan {$no}); hanya yang pertama dipakai.";

                continue;
            }

            if ($tipe === 'detail') {
                $detailTerakhir = $no;

                continue;
            }

            if ($tipe !== null) {
                $segmen[$no] = $tipe;
            }
        }

        if ($roBaris === null) {
            return null;
        }

        // Rentang tiap segmen: sampai baris sebelum segmen berikutnya; segmen terakhir sampai badan terakhir.
        $mulai = array_keys($segmen);
        $akhirBadan = max(end($mulai), $detailTerakhir);
        $akhir = [];
        foreach ($mulai as $i => $no) {
            $akhir[$no] = isset($mulai[$i + 1]) ? $mulai[$i + 1] - 1 : $akhirBadan;
        }

        // Pass 2: susun hierarki kategori -> kelompok.
        $kategori = [];
        $k = null; // indeks kategori aktif
        $g = null; // indeks kelompok aktif dalam kategori aktif

        foreach ($segmen as $no => $tipe) {
            $kode = $rows[$no][0];
            $nama = $rows[$no][1];

            if ($tipe === 'komponen') {
                $kategori[] = [
                    'kode_kategori' => $kode,
                    'nama_kategori' => $nama,
                    'baris_awal' => $no,
                    'baris_akhir' => $akhir[$no],
                    'urutan' => count($kategori) + 1,
                    'kelompok' => [],
                ];
                $k = count($kategori) - 1;
                $g = null;
            } elseif ($tipe === 'sub') {
                if ($k === null) {
                    $peringatan[] = "Sub {$kode} (baris {$no}) berada sebelum komponen manapun; baris ini tidak dapat dipilih.";

                    continue;
                }

                $kategori[$k]['kelompok'][] = [
                    'kode_kelompok' => $kode,
                    'nama_kelompok' => $nama !== '' ? $nama : null,
                    'baris_awal' => $no,
                    'baris_akhir' => $akhir[$no],
                    'urutan' => count($kategori[$k]['kelompok']) + 1,
                ];
                $g = count($kategori[$k]['kelompok']) - 1;
            } elseif ($tipe === 'akun') {
                if ($g === null) {
                    $peringatan[] = "Akun {$kode} (baris {$no}) tidak berada di dalam kelompok; baris ini tidak dapat dipilih.";

                    continue;
                }

                $kategori[$k]['kelompok'][$g]['baris_akhir'] = $akhir[$no];
            }
        }

        array_push($peringatan, ...$this->peringatanStruktur($kategori));

        [$kolomAkhir, $barisCetakAkhir] = $this->areaCetak($ws);

        return [
            'kode_sheet' => $ws->getTitle(),
            'nama_sheet' => $rows[$roBaris][1] !== '' ? $rows[$roBaris][1] : $ws->getTitle(),
            'urutan' => $urutan,
            'ro_baris_awal' => $roBaris,
            'ro_baris_akhir' => $akhir[$roBaris],
            'kolom_akhir' => $kolomAkhir,
            'header' => $roBaris > 1 ? ['baris_awal' => 1, 'baris_akhir' => $roBaris - 1] : null,
            // Footer: setelah segmen terakhir sampai batas area cetak (atau baris data terakhir). Kosong => null.
            'footer' => $barisCetakAkhir > $akhirBadan
                ? ['baris_awal' => $akhirBadan + 1, 'baris_akhir' => $barisCetakAkhir]
                : null,
            'kategori' => $kategori,
            'peringatan' => $peringatan,
        ];
    }

    /**
     * Kolom A dan B saja, nomor baris 1-based, nilai di-trim jadi string.
     *
     * @return array<int, array{0: string, 1: string}>
     */
    private function bacaKolomAB(Worksheet $ws): array
    {
        $data = $ws->rangeToArray('A1:B'.$ws->getHighestDataRow(), null, false, false, false);

        $rows = [];
        foreach ($data as $i => [$a, $b]) {
            $rows[$i + 1] = [trim((string) $a), trim((string) $b)];
        }

        return $rows;
    }

    private function tipeBaris(string $a, string $b): ?string
    {
        if (preg_match(self::POLA_RO, $a)) {
            return 'ro';
        }
        if (preg_match(self::POLA_KOMPONEN, $a)) {
            return 'komponen';
        }
        if (preg_match(self::POLA_SUB, $a)) {
            return 'sub';
        }
        if (preg_match(self::POLA_AKUN, $a)) {
            return 'akun';
        }
        if ($a === '' && str_starts_with($b, '-')) {
            return 'detail';
        }

        return null;
    }

    /**
     * @return array{0: string, 1: int} [kolom terakhir, baris terakhir]. Tanpa area cetak:
     *                                  kolom/baris data terakhir.
     */
    private function areaCetak(Worksheet $ws): array
    {
        $area = $ws->getPageSetup()->getPrintArea();

        if (is_string($area) && $area !== '') {
            $pertama = explode(',', str_replace('$', '', $area))[0];
            if (str_contains($pertama, '!')) {
                $pertama = substr($pertama, strrpos($pertama, '!') + 1);
            }

            [, $ujung] = Coordinate::rangeBoundaries($pertama);

            return [Coordinate::stringFromColumnIndex($ujung[0]), (int) $ujung[1]];
        }

        return [$ws->getHighestDataColumn(), $ws->getHighestDataRow()];
    }

    /**
     * Peringatan 03 (aturan validasi no. 6) + kode komponen ganda (melanggar unik per sheet).
     * Public supaya halaman admin bisa menghitung ulang dari data di database (peringatan hilang
     * saat admin mengisi nama). Bentuk masukan sama dengan kunci `kategori` hasil analisis.
     */
    public function peringatanStruktur(array $kategori): array
    {
        $peringatan = [];
        $barisPerKomponen = [];

        foreach ($kategori as $kat) {
            $barisPerKomponen[$kat['kode_kategori']][] = $kat['baris_awal'];

            $barisPerKode = [];
            foreach ($kat['kelompok'] as $kel) {
                $barisPerKode[$kel['kode_kelompok']][] = $kel['baris_awal'];

                if ($kel['nama_kelompok'] === null) {
                    $peringatan[] = "Kelompok {$kel['kode_kelompok']} pada komponen {$kat['kode_kategori']} (baris {$kel['baris_awal']}) tidak punya nama.";
                }
            }

            foreach ($barisPerKode as $kode => $baris) {
                if (count($baris) > 1) {
                    $peringatan[] = "Kode kelompok ganda: {$kode} pada komponen {$kat['kode_kategori']} (baris ".implode(', ', $baris).').';
                }
            }
        }

        foreach ($barisPerKomponen as $kode => $baris) {
            if (count($baris) > 1) {
                $peringatan[] = "Kode komponen ganda: {$kode} (baris ".implode(', ', $baris).').';
            }
        }

        return $peringatan;
    }
}
