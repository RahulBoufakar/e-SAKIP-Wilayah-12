<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class PengaturanAplikasi extends Model
{
    const CACHE_KEY = 'pengaturan_aplikasi';
    const CREATED_AT = null;

    protected $table = 'pengaturan_aplikasi';
    protected $fillable = ['nama_aplikasi', 'logo', 'favicon'];

    /**
     * Singleton: hanya satu baris (id=1) untuk seluruh pengaturan aplikasi.
     */
    public static function current(): self
    {
        return static::firstOrCreate(['id' => 1], ['nama_aplikasi' => 'eSAKIP LLDikti Wilayah XII']);
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? Storage::url($this->logo) : null;
    }

    public function getFaviconUrlAttribute(): ?string
    {
        return $this->favicon ? Storage::url($this->favicon) : null;
    }
}