<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Footer extends Model
{
    public $timestamps = false;

    protected $table = 'footer';
    protected $fillable = ['sheet_id', 'baris_awal', 'baris_akhir', 'otomatis'];

    protected $casts = [
        'otomatis' => 'boolean',
    ];

    public function sheet()
    {
        return $this->belongsTo(Sheet::class);
    }
}
