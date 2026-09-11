<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LaporanKinerja extends Model
{
    const UPDATED_AT = null; // append-only: status diupdate job, tapi created_at = tanggal generate

    protected $table = 'laporan_kinerja';
    protected $fillable = [
        'jenis', 'tahun_anggaran_id', 'bulan', 'triwulan_id',
        'versi', 'status', 'file_path', 'catatan', 'generated_by',
    ];

    private const NAMA_BULAN = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    public function tahunAnggaran()
    {
        return $this->belongsTo(TahunAnggaran::class);
    }

    public function triwulan()
    {
        return $this->belongsTo(Triwulan::class);
    }

    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /** "Juni 2026" / "Triwulan 1 2026" / "Tahun 2026" — tanpa versi. */
    public function getLabelPeriodeAttribute(): string
    {
        $tahun = $this->tahunAnggaran?->tahun ?? '-';

        return match ($this->jenis) {
            'bulanan' => (self::NAMA_BULAN[$this->bulan] ?? '-')." {$tahun}",
            'triwulanan' => 'Triwulan '.($this->triwulan?->urutan ?? '-')." {$tahun}",
            'tahunan' => "Tahun {$tahun}",
            default => "{$tahun}",
        };
    }

    /** Format label §4.7: "{Nama Periode} {Tahun} v{N}", mis. "Triwulan 1 2026 v1". */
    public function getLabelAttribute(): string
    {
        return "{$this->label_periode} v{$this->versi}";
    }

    /**
     * Versi berikutnya untuk kombinasi jenis+periode yang sama (§4.7).
     * Bulan/triwulan_id yang tidak relevan untuk jenis tsb wajib dikirim null
     * supaya perbandingan konsisten dengan whereNull.
     */
    public static function nextVersi(string $jenis, int $tahunAnggaranId, ?int $bulan = null, ?int $triwulanId = null): int
    {
        $max = static::where('jenis', $jenis)
            ->where('tahun_anggaran_id', $tahunAnggaranId)
            ->when($bulan !== null, fn ($q) => $q->where('bulan', $bulan), fn ($q) => $q->whereNull('bulan'))
            ->when($triwulanId !== null, fn ($q) => $q->where('triwulan_id', $triwulanId), fn ($q) => $q->whereNull('triwulan_id'))
            ->max('versi');

        return ((int) $max) + 1;
    }
}
