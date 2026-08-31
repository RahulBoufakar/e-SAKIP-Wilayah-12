<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pts extends Model
{
    protected $table = 'pts';
    protected $fillable = ['kode_pts', 'nama_pts', 'status_pts', 'akreditasi_pts'];

    public function usulanProgramKerja()
    {
        return $this->belongsToMany(UsulanProgramKerja::class, 'usulan_program_kerja_pts');
    }

}