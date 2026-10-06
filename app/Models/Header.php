<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Header extends Model
{
    public $timestamps = false;

    protected $table = 'header';
    protected $fillable = ['sheet_id', 'baris_awal', 'baris_akhir', 'otomatis'];

    protected $casts = [
        'otomatis' => 'boolean',
    ];

    public function sheet()
    {
        return $this->belongsTo(Sheet::class);
    }
}
