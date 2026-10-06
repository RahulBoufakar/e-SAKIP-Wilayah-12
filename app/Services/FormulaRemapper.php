<?php

namespace App\Services;

/**
 * RAB Generator 05-algoritma-perakit.md §5 — penulisan ulang rumus.
 *
 * Fungsi murni: tidak menyentuh workbook. Perakit membangun peta
 * `baris_lama => baris_baru` (hanya baris yang ikut), lalu memanggil remap()
 * untuk tiap sel berumus.
 *
 * Aturan:
 * - Rantai penjumlahan sel (>= 2 suku, mis. "=X22+X61+X108"): suku yang barisnya
 *   tidak ikut DIBUANG; bila tidak ada suku tersisa menjadi "=0".
 * - Selain itu (SUM, perkalian, rujukan tunggal "=X80", dst): semua rujukan
 *   dipetakan. Rujukan yang tidak terpetakan dibiarkan apa adanya dan DILAPORKAN
 *   (perakit harus menganggapnya kegagalan, T8 `unmapped_formulas` = 0).
 * - Rentang (X27:X29) dipetakan hanya bila SEMUA baris di dalamnya ikut dan tetap
 *   berurutan di hasil. Kalau tidak, hasilnya diam-diam salah, jadi dilaporkan.
 * - Rumus lintas-sheet (mengandung "!") tidak diubah dan dilaporkan.
 *
 * Tidak ditangani: rujukan seluruh kolom/baris (X:X, 5:5) dan nama terdefinisi.
 */
class FormulaRemapper
{
    private const SEL = '\$?[A-Z]{1,3}\$?\d+';

    /** Satu rujukan sel atau rentang, bukan bagian dari nama fungsi (LOG10( ) atau token lain. */
    private const POLA_REF = '/(?<![A-Za-z0-9_.])(\$?[A-Z]{1,3})(\$?)(\d+)(?::(\$?[A-Z]{1,3})(\$?)(\d+))?(?![A-Za-z0-9_(])/';

    /**
     * @param  array<int, int>  $rowMap  baris lama => baris baru
     * @return array{formula: string, tidak_terpetakan: string[], lintas_sheet: bool}
     */
    public function remap(string $formula, array $rowMap): array
    {
        $hasil = ['formula' => $formula, 'tidak_terpetakan' => [], 'lintas_sheet' => false];

        if (! str_starts_with($formula, '=')) {
            return $hasil;
        }

        // Pisahkan literal string ("...") agar isinya tidak ikut diproses.
        $bagian = preg_split('/("(?:[^"]|"")*")/', $formula, -1, PREG_SPLIT_DELIM_CAPTURE);

        foreach ($bagian as $i => $teks) {
            if ($i % 2 === 0 && str_contains($teks, '!')) {
                $hasil['lintas_sheet'] = true;

                return $hasil;
            }
        }

        if (preg_match('/^=\s*'.self::SEL.'(\s*\+\s*'.self::SEL.')+\s*$/', $formula)) {
            return $this->remapRantai($formula, $rowMap, $hasil);
        }

        foreach ($bagian as $i => $teks) {
            if ($i % 2 === 0) {
                $bagian[$i] = preg_replace_callback(self::POLA_REF, function ($m) use ($rowMap, &$hasil) {
                    return $this->petakanRef($m, $rowMap, $hasil);
                }, $teks);
            }
        }

        $hasil['formula'] = implode('', $bagian);

        return $hasil;
    }

    private function remapRantai(string $formula, array $rowMap, array $hasil): array
    {
        preg_match_all('/'.self::SEL.'/', $formula, $suku);

        $tersisa = [];
        foreach ($suku[0] as $ref) {
            preg_match('/^(\$?[A-Z]{1,3}\$?)(\d+)$/', $ref, $m);

            if (isset($rowMap[(int) $m[2]])) {
                $tersisa[] = $m[1].$rowMap[(int) $m[2]];
            }
        }

        $hasil['formula'] = $tersisa === [] ? '=0' : '='.implode('+', $tersisa);

        return $hasil;
    }

    private function petakanRef(array $m, array $rowMap, array &$hasil): string
    {
        [$semua, $kolomAwal, $dolarAwal, $barisAwal] = $m;
        $adaRentang = isset($m[4]);

        if (! $adaRentang) {
            if (! isset($rowMap[(int) $barisAwal])) {
                $hasil['tidak_terpetakan'][] = $semua;

                return $semua;
            }

            return $kolomAwal.$dolarAwal.$rowMap[(int) $barisAwal];
        }

        [$kolomAkhir, $dolarAkhir, $barisAkhir] = [$m[4], $m[5], (int) $m[6]];
        $dari = min((int) $barisAwal, $barisAkhir);
        $sampai = max((int) $barisAwal, $barisAkhir);

        if (! $this->rentangUtuh($dari, $sampai, $rowMap)) {
            $hasil['tidak_terpetakan'][] = $semua;

            return $semua;
        }

        return $kolomAwal.$dolarAwal.$rowMap[(int) $barisAwal].':'.$kolomAkhir.$dolarAkhir.$rowMap[$barisAkhir];
    }

    /** Semua baris rentang ikut dan berurutan rapat di hasil (tidak ada yang terbuang di tengah). */
    private function rentangUtuh(int $dari, int $sampai, array $rowMap): bool
    {
        for ($r = $dari; $r <= $sampai; $r++) {
            if (! isset($rowMap[$r])) {
                return false;
            }
            if ($r > $dari && $rowMap[$r] !== $rowMap[$r - 1] + 1) {
                return false;
            }
        }

        return true;
    }
}
