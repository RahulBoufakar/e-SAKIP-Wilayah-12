<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Triwulan extends Model
{
    public $timestamps = false;
    protected $table = 'triwulan';
    protected $fillable = ['kode', 'urutan'];

    public function statuses()
    {
        return $this->hasMany(TriwulanStatus::class);
    }

    public function rencanaAksi()
    {
        return $this->hasMany(RencanaAksi::class);
    }
}
