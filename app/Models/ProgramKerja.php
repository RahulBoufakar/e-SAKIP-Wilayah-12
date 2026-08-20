<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgramKerja extends Model
{
    protected $table = 'program_kerja';
    protected $fillable = ['usulan_program_kerja_id', 'kode_proker'];

    public function usulanProgramKerja()
    {
        return $this->belongsTo(UsulanProgramKerja::class);
    }
}
