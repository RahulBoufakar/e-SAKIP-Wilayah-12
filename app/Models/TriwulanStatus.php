<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TriwulanStatus extends Model
{
    const CREATED_AT = null;
    const UPDATED_AT = 'updated_at';

    protected $table = 'triwulan_status';
    protected $fillable = ['tahun_anggaran_id', 'triwulan_id', 'status'];

    public function tahunAnggaran()
    {
        return $this->belongsTo(TahunAnggaran::class);
    }

    public function triwulan()
    {
        return $this->belongsTo(Triwulan::class);
    }

    /**
     * Rule R-1: aktifkan satu Triwulan untuk satu Tahun Anggaran, otomatis
     * menonaktifkan Triwulan lain di tahun yang sama (atomic switch).
     */
    public static function activate(int $triwulanId, int $tahunAnggaranId): ?self
    {
        return DB::transaction(function () use ($triwulanId, $tahunAnggaranId) {
            // Jika triwulanId = 0, berarti nonaktifkan semua
             if ($triwulanId === 0) {
                static::where('tahun_anggaran_id', $tahunAnggaranId)->update(['status' => 'non_aktif']);

                // Hapus cache triwulan aktif untuk tahun anggaran ini
                Cache::forget("context_triwulan_aktif_{$tahunAnggaranId}");

                return null;
            }

            // Nonaktifkan semua triwulan di tahun anggaran yang sama
            static::where('tahun_anggaran_id', $tahunAnggaranId)->update(['status' => 'non_aktif']);

            // Aktifkan triwulan yang dipilih
            $status = static::firstOrCreate(
                ['tahun_anggaran_id' => $tahunAnggaranId, 'triwulan_id' => $triwulanId],
                ['status' => 'non_aktif']
            );
            $status->update(['status' => 'aktif']);

            // Hapus cache triwulan aktif untuk tahun anggaran ini
            Cache::forget("context_triwulan_aktif_{$tahunAnggaranId}");

            return $status->fresh();
        });
    }
}
