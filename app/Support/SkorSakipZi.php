<?php

namespace App\Support;

/**
 * Spek Capaian Kinerja Hybrid §5 Pola 3 & §11 poin 1/3 — pemetaan predikat
 * kategorikal SAKIP/ZI ke skor tetap. Dipakai IKU 3 (Tata Kelola).
 * Keputusan final: CC=50, C=30; opsi belum_diterbitkan=0 untuk kasus skor
 * tahun berjalan belum terbit resmi.
 */
class SkorSakipZi
{
    private const SKOR_SAKIP = [
        'AA' => 90,
        'A' => 80,
        'BB' => 70,
        'B' => 60,
        'CC' => 50,
        'C' => 30,
        'belum_diterbitkan' => 0,
    ];

    private const SKOR_ZI = [
        'WBBM' => 100,
        'WBK' => 90,
        'menuju_wbk' => 80,
        'belum_diterbitkan' => 0,
    ];

    public static function hitung(string $predikatSakip, string $predikatZi): float
    {
        $skorSakip = self::SKOR_SAKIP[$predikatSakip] ?? 0;
        $skorZi = self::SKOR_ZI[$predikatZi] ?? 0;

        return round(($skorSakip + $skorZi) / 2, 2);
    }

    public static function opsiSakip(): array
    {
        return array_keys(self::SKOR_SAKIP);
    }

    public static function opsiZi(): array
    {
        return array_keys(self::SKOR_ZI);
    }

    public static function skorSakip(string $predikat): int { return self::SKOR_SAKIP[$predikat] ?? 0; }
    public static function skorZi(string $predikat): int   { return self::SKOR_ZI[$predikat] ?? 0; }

    public static function label(string $predikat, int $skor): string
    {
        return $predikat === 'belum_diterbitkan' ? "Belum diterbitkan ({$skor})" : "{$predikat} ({$skor})";
    }
}
