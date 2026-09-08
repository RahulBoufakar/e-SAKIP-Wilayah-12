<?php

namespace App\Models;

use App\Formulas\FormulaRegistry;
use App\Models\Concerns\HasStatusPengiriman;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class CapaianKinerja extends Model
{
    use HasStatusPengiriman {
        setujui as protected traitSetujui;
    }

    protected $table = 'capaian_kinerja';
    protected $fillable = ['iku_id', 'triwulan_id', 'tahun_anggaran_id', 'target', 'realisasi', 'variabel', 'status', 'catatan_revisi'];

    protected $casts = [
        'target' => 'decimal:2',
        'realisasi' => 'decimal:2',
        'variabel' => 'array',
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

    public function dokumen()
    {
        return $this->hasMany(CapaianKinerjaDokumen::class, 'capaian_kinerja_id');
    }

    /**
     * Nilai Realisasi Triwulan Ini = (Realisasi Triwulan Ini ÷ Target PK) x 100%.
     * Dihitung terhadap Target PK (target akhir/tahunan pada master IKU), BUKAN
     * terhadap `target` per-triwulan pada tabel ini — supaya persentase tidak
     * melonjak ratusan persen hanya karena target per-triwulan yang di-set kecil
     * (mis. target TW1 = 25 sedangkan realisasi = 91 -> 364% pada formula lama).
     * Wajib eager-load relasi `iku` (dengan kolom target_pk) di pemanggil untuk
     * menghindari N+1.
     */
    public function getCapaianAttribute(): ?float
    {
        if ($this->realisasi === null) {
            return null;
        }

        $targetPk = (float) ($this->iku?->target_pk ?? 0);

        if ($targetPk <= 0) {
            return null;
        }

        return round(((float) $this->realisasi / $targetPk) * 100, 2);
    }

    /**
     * Satu sumber kebenaran untuk "data sudah lengkap": dipakai oleh can_kirim,
     * guard kirim() di Controller Tim Kerja, guard setujui() di bawah, dan
     * disabled-state tombol Setujui di view Validator — supaya tidak ada
     * jalur (form, tombol, request manual) yang bisa meloloskan data kosong.
     */
    public function isDataLengkap(): bool
    {
        if ($this->realisasi === null) {
            return false;
        }

        $formula = FormulaRegistry::resolve($this->iku->formula_kode);
        if (! $formula) {
            return true;
        }

        $variabel = $this->variabel ?? [];
        foreach ($formula->variables() as $var) {
            $nilai = $variabel[$var['key']] ?? null;
            if ($nilai === null || $nilai === '') {
                return false;
            }
        }

        return true;
    }

    public function getCanKirimAttribute(): bool
    {
        return $this->isDataLengkap()
            && $this->dokumen()->count() >= 1
            && in_array($this->status, ['draft', 'ditolak'], true);
    }

    /**
     * Override setujui() dari trait: tambah guard data lengkap sebelum delegasi
     * ke logika asli trait (state check menunggu_validasi -> disetujui).
     * Ini pertahanan sisi server — bukan pengganti disabled-state di UI,
     * melainkan pelengkap kalau ada yang mem-bypass lewat request manual.
     */
    public function setujui(): static
    {
        if (! $this->isDataLengkap()) {
            throw new RuntimeException('Data variabel/realisasi belum lengkap, tidak dapat disetujui.');
        }

        return $this->traitSetujui();
    }
}