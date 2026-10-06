<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class FileExcel extends Model
{
    const CREATED_AT = 'tanggal_upload';
    const UPDATED_AT = null;

    protected $table = 'file_excel';
    protected $fillable = ['nama_file', 'path', 'hash_sha256', 'aktif', 'diunggah_oleh', 'peringatan'];

    protected $casts = [
        'aktif' => 'boolean',
        'peringatan' => 'array',
    ];

    public function sheets()
    {
        return $this->hasMany(Sheet::class);
    }

    public function pengunggah()
    {
        return $this->belongsTo(User::class, 'diunggah_oleh');
    }

    /** Jumlah peringatan hasil deteksi yang tersimpan (tingkat file + semua sheet). */
    public function getJumlahPeringatanAttribute(): int
    {
        $peringatan = $this->peringatan ?? [];

        return count($peringatan['file'] ?? []) + array_sum(array_map('count', $peringatan['sheet'] ?? []));
    }

    public function scopeAktif($query)
    {
        return $query->where('aktif', true);
    }

    /** Hanya satu file master aktif: nonaktifkan yang lain, aktifkan ini (atomik). */
    public function aktifkan(): void
    {
        DB::transaction(function () {
            static::where('id', '!=', $this->id)->update(['aktif' => false]);
            $this->update(['aktif' => true]);
        });
    }
}
