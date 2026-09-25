<?php

namespace App\Models;

use App\Models\TimKerja;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class PesanKontak extends Model
{
    const UPDATED_AT = null;

    protected $table = 'pesan_kontak';
    protected $fillable = ['nama', 'email', 'tim_kerja_id', 'gambar', 'pesan'];

    public function timKerja()
    {
        return $this->belongsTo(TimKerja::class);
    }

    public function getGambarUrlAttribute(): ?string
    {
        return $this->gambar ? Storage::url($this->gambar) : null;
    }
}
