<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimKerja extends Model
{
    public $timestamps = false;
    protected $table = 'tim_kerja';
    protected $fillable = ['nama_tim'];

    public function iku()
    {
        return $this->hasMany(Iku::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_tim_kerja');
    }
}
