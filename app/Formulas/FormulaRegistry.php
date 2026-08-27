<?php

namespace App\Formulas;

class FormulaRegistry
{
    private const MAP = [
        'iku_1_1_kepuasan' => Iku11KepuasanFormula::class,
        'iku_1_2_akreditasi' => Iku12AkreditasiFormula::class,
        'iku_1_3_sakip_zi' => Iku13SakipZiFormula::class,
        'iku_2_1_fasilitasi_mutu' => Iku21FasilitasiMutuFormula::class,
        'iku_2_2_kebijakan_anti' => Iku22KebijakanAntiFormula::class,
        'iku_3_1_fasilitasi_kemahasiswaan' => Iku31FasilitasiKemahasiswaanFormula::class,
        'iku_3_2_dosen_jafung' => Iku32DosenJafungFormula::class,
        'iku_3_3_fasilitasi_penelitian' => Iku33FasilitasiPenelitianFormula::class,
    ];

    // Nomor pada 'kode' IKU (mis. "1.1" dari "[iku 1.1]") -> formula_kode.
    // Dipakai untuk auto-select dropdown formula saat Tambah/Edit IKU.
    private const NOMOR_MAP = [
        '1.1' => 'iku_1_1_kepuasan',
        '1.2' => 'iku_1_2_akreditasi',
        '1.3' => 'iku_1_3_sakip_zi',
        '2.1' => 'iku_2_1_fasilitasi_mutu',
        '2.2' => 'iku_2_2_kebijakan_anti',
        '3.1' => 'iku_3_1_fasilitasi_kemahasiswaan',
        '3.2' => 'iku_3_2_dosen_jafung',
        '3.3' => 'iku_3_3_fasilitasi_penelitian',
    ];

    public static function resolve(?string $formulaKode): ?FormulaInterface
    {
        $class = self::MAP[$formulaKode] ?? null;

        return $class ? new $class() : null;
    }

    public static function keys(): array
    {
        return array_keys(self::MAP);
    }

    /** Untuk dropdown Admin: kode => ['label' => ..., 'description' => ...] */
    public static function list(): array
    {
        return collect(self::MAP)->map(fn ($class) => [
            'label' => (new $class())->label(),
            'description' => (new $class())->description(),
        ])->all();
    }

    /** Peta nomor->formula_kode, dikirim ke Alpine untuk auto-select. */
    public static function nomorMap(): array
    {
        return self::NOMOR_MAP;
    }

    public static function resolveByNomor(string $nomor): ?string
    {
        return self::NOMOR_MAP[$nomor] ?? null;
    }
}