<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

/**
 * Header agregat — 1 baris per (IKU, Triwulan, Tahun) — Spek Capaian Kinerja
 * Hybrid §2 & §4.1. Sejak refaktor hybrid:
 *
 * - `realisasi_otomatis`/`realisasi` dihitung dari baris detail (10 tabel,
 *   lihat komponenUntukTipe()) berstatus 'disetujui', lewat
 *   App\Services\CapaianKinerjaHitungService — BUKAN lagi dari `variabel`
 *   JSON + FormulaRegistry (dihapus, lihat catatan cleanup Fase 7).
 *
 * - `status` header di sini adalah NILAI TURUNAN, bukan hasil transisi
 *   langsung lewat kirim()/setujui()/tolak() pada header (beda dari pola
 *   HasStatusPengiriman di modul lain). Approve/reject sesungguhnya terjadi
 *   di level BARIS (App\Models\Concerns\HasRowValidation, kolom
 *   status_validasi pada 10 tabel detail). Header disinkronkan lewat
 *   syncStatusFromBaris(), dipanggil controller setiap kali baris berubah.
 *
 * - `dokumen()` / CapaianKinerjaDokumen (bukti generik per-header dari
 *   arsitektur lama) TIDAK dipakai lagi oleh 9 IKU hybrid — tiap baris kini
 *   punya `file_bukti_dukung` sendiri. Relasi ini DIPERTAHANKAN (tidak
 *   dihapus) semata untuk kompatibilitas mundur; lihat README refaktor
 *   bagian cleanup untuk detail.
 */
class CapaianKinerja extends Model
{
    protected $table = 'capaian_kinerja';
    protected $fillable = [
        'iku_id', 'triwulan_id', 'tahun_anggaran_id',
        'target', 'realisasi', 'realisasi_otomatis', 'realisasi_override',
        'status', 'catatan_revisi',
    ];

    protected $casts = [
        'target' => 'decimal:2',
        'realisasi' => 'decimal:2',
        'realisasi_otomatis' => 'decimal:2',
        'realisasi_override' => 'boolean',
    ];

    public function iku()
    {
        return $this->belongsTo(Iku::class);
    }

    public function triwulan()
    {
        return $this->belongsTo(Triwulan::class);
    }

    public function tahunAnggaran()
    {
        return $this->belongsTo(TahunAnggaran::class);
    }

    /**
     * @deprecated untuk 9 IKU hybrid — lihat catatan class di atas.
     * Dipertahankan agar tidak menghapus data/fitur di luar scope refaktor ini.
     */
    public function dokumen()
    {
        return $this->hasMany(CapaianKinerjaDokumen::class, 'capaian_kinerja_id');
    }

    public function kepuasanLayanan(): HasMany
    {
        return $this->hasMany(CapaianKepuasanLayanan::class);
    }

    public function akreditasiPts(): HasMany
    {
        return $this->hasMany(CapaianAkreditasiPts::class);
    }

    public function penggabunganPts(): HasMany
    {
        return $this->hasMany(CapaianPenggabunganPts::class);
    }

    public function tataKelola(): HasMany
    {
        return $this->hasMany(CapaianTataKelola::class);
    }

    public function fasilitasiMutuPts(): HasMany
    {
        return $this->hasMany(CapaianFasilitasiMutuPts::class);
    }

    public function kebijakanPpks(): HasMany
    {
        return $this->hasMany(CapaianKebijakanPpks::class);
    }

    public function fasilitasiKemahasiswaan(): HasMany
    {
        return $this->hasMany(CapaianFasilitasiKemahasiswaan::class);
    }

    public function dosenNaikJafung(): HasMany
    {
        return $this->hasMany(CapaianDosenNaikJafung::class);
    }

    public function fasilitasiPenelitian(): HasMany
    {
        return $this->hasMany(CapaianFasilitasiPenelitian::class);
    }

    public function nilaiRka(): HasMany
    {
        return $this->hasMany(CapaianNilaiRka::class);
    }

    /**
     * SATU-SATUNYA tempat pemetaan tipe_iku -> [nama_komponen => model class]
     * didefinisikan (Spek §9: resolusi eksplisit lewat match/lookup langsung,
     * BUKAN interface/registry generik — draf awal dengan pivot
     * iku_capaian_tipe + CapaianTipeInterface sudah ditolak, lihat §2 spek).
     * Dipakai controller untuk CRUD baris & eager-load, dan oleh relasi()
     * di bawah.
     */
    public static function komponenUntukTipe(?string $tipeIku): array
    {
        return match ($tipeIku) {
            'kepuasan_layanan' => ['utama' => CapaianKepuasanLayanan::class],
            'arsitektur_pts' => [
                'akreditasi' => CapaianAkreditasiPts::class,
                'penggabungan' => CapaianPenggabunganPts::class,
            ],
            'tata_kelola' => ['utama' => CapaianTataKelola::class],
            'fasilitasi_mutu_pts' => ['utama' => CapaianFasilitasiMutuPts::class],
            'kebijakan_ppks' => ['utama' => CapaianKebijakanPpks::class],
            'fasilitasi_kemahasiswaan' => ['utama' => CapaianFasilitasiKemahasiswaan::class],
            'dosen_naik_jafung' => ['utama' => CapaianDosenNaikJafung::class],
            'fasilitasi_penelitian' => ['utama' => CapaianFasilitasiPenelitian::class],
            'nilai_rka' => ['utama' => CapaianNilaiRka::class],
            default => [],
        };
    }

    /**
     * Relasi hasMany aktif untuk komponen tertentu (mis. 'utama', atau
     * 'akreditasi'/'penggabungan' khusus arsitektur_pts), sesuai tipe_iku
     * milik header ini. Ini titik resolusi "{baris} di-resolve controller
     * berdasarkan tipe_iku" yang diminta Spek §9.
     */
    public function relasi(string $komponen): HasMany
    {
        $kelasKomponen = static::komponenUntukTipe($this->iku->tipe_iku)[$komponen] ?? null;

        if (! $kelasKomponen) {
            throw new InvalidArgumentException(
                "Komponen '{$komponen}' tidak dikenal untuk tipe_iku '{$this->iku->tipe_iku}'."
            );
        }

        return $this->hasMany($kelasKomponen);
    }

    /**
     * Rekalkulasi status agregat header dari status_validasi seluruh baris
     * (lintas komponen bila IKU punya >1 tabel, mis. arsitektur_pts) — Spek
     * §7: "disetujui hanya jika semua baris disetujui". Dipanggil setelah
     * baris berubah (kirim/setujui/tolak/bulk), BUKAN dipicu aksi langsung
     * pada header ini.
     */
    public function syncStatusFromBaris(): void
    {
        $semuaStatusBaris = collect(array_keys($this->iku->komponenCapaian()))
            ->flatMap(fn ($komponen) => $this->relasi($komponen)->pluck('status_validasi'));

        $this->status = match (true) {
            $semuaStatusBaris->isEmpty() => 'draft',
            $semuaStatusBaris->contains('ditolak') => 'ditolak',
            $semuaStatusBaris->contains('menunggu_validasi') => 'menunggu_validasi',
            $semuaStatusBaris->every(fn ($s) => $s === 'disetujui') => 'disetujui',
            default => 'draft',
        };

        $this->save();
    }

    /** True jika ada baris draft/ditolak yang siap dikirim untuk validasi. */
    public function getCanKirimAttribute(): bool
    {
        return collect(array_keys($this->iku->komponenCapaian()))
            ->contains(fn ($komponen) => $this->relasi($komponen)
                ->whereIn('status_validasi', ['draft', 'ditolak'])
                ->exists());
    }
}