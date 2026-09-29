<?php

namespace App\Services;

use App\Models\CapaianKinerja;
use App\Models\JumlahPts;
use App\Models\JumlahPublikasi;
use App\Support\SkorSakipZi;

/**
 * Spek Capaian Kinerja Hybrid §5 & §6 — satu method eksplisit per tipe_iku.
 *
 * SENGAJA bukan interface/registry generik (§2 "Prinsip Arsitektur": draf
 * awal ber-abstraksi CapaianTipeInterface + CapaianTipeRegistry + pivot
 * iku_capaian_tipe sudah DITOLAK karena tidak bisa merepresentasikan
 * keunikan tiap formula resmi — dedup per PTS, bukti wajib, kombinasi
 * multi-tabel, dst). Class ini murni kumpulan method konkret, dipanggil
 * lewat satu match() di hitung() — bukan resolusi polimorfik/pivot DB.
 *
 * Semua kalkulasi HANYA menghitung baris berstatus 'disetujui' (§6) —
 * relasi pada CapaianKinerja sudah discope ke 1 (iku, triwulan, tahun)
 * tertentu, jadi tidak perlu parameter tw/tahun tambahan seperti pseudokode
 * awal di draf spek (redundan karena sudah discope oleh $capaian sendiri).
 */
class CapaianKinerjaHitungService
{
    private array $statusDihitung = ['disetujui'];

    // Nilai Realisasi akhir yang tidak lebih dari target PK. Dipakai untuk semua tipe_iku.
    public function hitung(CapaianKinerja $capaian): ?float
    {
        return $this->batasiKeTargetPk($this->hitungMentah($capaian), $capaian);
    }
    
    public function hitungMentah(CapaianKinerja $capaian): ?float
    {
        return match ($capaian->iku->tipe_iku) {
            'kepuasan_layanan' => $this->kepuasanLayanan($capaian),
            'arsitektur_pts' => $this->arsitekturPts($capaian),
            'tata_kelola' => $this->tataKelola($capaian),
            'fasilitasi_mutu_pts' => $this->fasilitasiMutuPts($capaian),
            'kebijakan_ppks' => $this->kebijakanPpks($capaian),
            'fasilitasi_kemahasiswaan' => $this->fasilitasiKemahasiswaan($capaian),
            'dosen_naik_jafung' => $this->dosenNaikJafung($capaian),
            'fasilitasi_penelitian' => $this->fasilitasiPenelitian($capaian),
            'nilai_rka' => $this->nilaiRka($capaian),
            default => null,
        };
    }

    /** Data untuk panel preview. $statuses = baris berstatus apa saja yang ikut dihitung (proyeksi). */
    public function ringkasan(CapaianKinerja $capaian, array $statuses): array
    {
        $sebelumnya = $this->statusDihitung;
        $this->statusDihitung = $statuses;
        try {
            $mentah = $this->hitungMentah($capaian);
        } finally {
            $this->statusDihitung = $sebelumnya;
        }

        $realisasi = $this->batasiKeTargetPk($mentah, $capaian);

        return [
            'realisasi_mentah' => $mentah,
            'realisasi'        => $realisasi,
            'dibatasi'         => $mentah !== null && $realisasi !== null && $mentah > $realisasi,
            'target_pk'        => (float) $capaian->iku->target_pk,
            'target_triwulan'  => $capaian->target !== null ? (float) $capaian->target : null,
            'satuan'           => $capaian->iku->satuan,
        ];
    }

    private function batasiKeTargetPk(?float $nilai, CapaianKinerja $capaian): ?float
    {
        $targetPk = (float) $capaian->iku->target_pk;

        return ($nilai !== null && $targetPk > 0) ? min($nilai, $targetPk) : $nilai;
    }

    // IKU 1 — Pola 1: rasio SUM. SUM(responden_puas) / SUM(total_responden) x 100%.
    private function kepuasanLayanan(CapaianKinerja $capaian): ?float
    {
        $baris = $capaian->kepuasanLayanan()->statusIn($this->statusDihitung)->get();
        $totalResponden = $baris->sum('total_responden');

        return $totalResponden > 0
            ? round($baris->sum('responden_puas') / $totalResponden * 100, 2)
            : null;
    }

    // IKU 2 — Pola 2: gabungan 2 tabel, rasio COUNT thd Jumlah PTS.
    private function arsitekturPts(CapaianKinerja $capaian): ?float
    {
        $jumlahAkreditasi = $capaian->akreditasiPts()->statusIn($this->statusDihitung)->count();
        $jumlahPenggabungan = $capaian->penggabunganPts()->statusIn($this->statusDihitung)->count();
        $totalPts = $this->jumlahPts($capaian);

        return $totalPts > 0
            ? round(($jumlahAkreditasi + $jumlahPenggabungan) / $totalPts * 100, 2)
            : null;
    }

    // IKU 3 — Pola 3: mapping kategorikal -> skor tetap. Ambil baris disetujui terbaru.
    private function tataKelola(CapaianKinerja $capaian): ?float
    {
        $baris = $capaian->tataKelola()->statusIn($this->statusDihitung)->latest()->first();

        return $baris ? SkorSakipZi::hitung($baris->predikat_sakip, $baris->predikat_zi) : null;
    }

    // IKU 4 — COUNT DISTINCT PTS thd Jumlah PTS.
    private function fasilitasiMutuPts(CapaianKinerja $capaian): ?float
    {
        $jumlahPtsDifasilitasi = $capaian->fasilitasiMutuPts()->statusIn($this->statusDihitung)->distinct('pts_id')->count('pts_id');
        $totalPts = $this->jumlahPts($capaian);

        return $totalPts > 0 ? round($jumlahPtsDifasilitasi / $totalPts * 100, 2) : null;
    }

    // IKU 5 — Pola 4: rasio COUNT DISTINCT PTS dengan syarat 3 komponen implementasi terisi.
    private function kebijakanPpks(CapaianKinerja $capaian): ?float
    {
        $jumlahPtsLengkap = $capaian->kebijakanPpks()->statusIn($this->statusDihitung)
            ->whereNotNull('file_implementasi_ppks')
            ->whereNotNull('file_implementasi_anti_narkoba')
            ->whereNotNull('file_implementasi_anti_korupsi')
            ->distinct('pts_id')->count('pts_id');
        $totalPts = $this->jumlahPts($capaian);

        return $totalPts > 0 ? round($jumlahPtsLengkap / $totalPts * 100, 2) : null;
    }

    // IKU 6 — sama pola dengan IKU 4.
    private function fasilitasiKemahasiswaan(CapaianKinerja $capaian): ?float
    {
        $jumlahPtsDifasilitasi = $capaian->fasilitasiKemahasiswaan()->statusIn($this->statusDihitung)->distinct('pts_id')->count('pts_id');
        $totalPts = $this->jumlahPts($capaian);

        return $totalPts > 0 ? round($jumlahPtsDifasilitasi / $totalPts * 100, 2) : null;
    }

    // IKU 7 — Pola 5: COUNT DISTINCT dedup non-PTS (NIDN), satuan Orang, TANPA persentase.
    private function dosenNaikJafung(CapaianKinerja $capaian): ?float
    {
        return (float) $capaian->dosenNaikJafung()->statusIn($this->statusDihitung)->distinct('nidn')->count('nidn');
    }

    // IKU 8 — sama pola rasio COUNT DISTINCT PTS, TAPI denominator jumlah_publikasi (bukan jumlah_pts).
    private function fasilitasiPenelitian(CapaianKinerja $capaian): ?float
    {
        $jumlahPtsDifasilitasi = $capaian->fasilitasiPenelitian()->statusIn($this->statusDihitung)->distinct('pts_id')->count('pts_id');
        $totalPublikasi = (int) JumlahPublikasi::where('tahun_anggaran_id', $capaian->tahun_anggaran_id)->value('jumlah');

        return $totalPublikasi > 0 ? round($jumlahPtsDifasilitasi / $totalPublikasi * 100, 2) : null;
    }

    // IKU 9 — Pola 6: nilai baris disetujui terbaru, tanpa pembagian.
    private function nilaiRka(CapaianKinerja $capaian): ?float
    {
        $nilai = $capaian->nilaiRka()->statusIn($this->statusDihitung)->latest()->value('nilai_rka');

        return $nilai !== null ? (float) $nilai : null;
    }

    // Ambil jumlah PTS dari tabel JumlahPts, untuk denominator beberapa IKU.
    private function jumlahPts(CapaianKinerja $capaian): int
    {
        return (int) JumlahPts::where('tahun_anggaran_id', $capaian->tahun_anggaran_id)->value('jumlah');
    }
}
