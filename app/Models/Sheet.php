<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sheet extends Model
{
    public $timestamps = false;

    protected $table = 'sheet';
    protected $fillable = [
        'file_excel_id', 'kode_sheet', 'nama_sheet', 'urutan',
        'ro_baris_awal', 'ro_baris_akhir', 'kolom_akhir',
    ];

    public function fileExcel()
    {
        return $this->belongsTo(FileExcel::class);
    }

    public function header()
    {
        return $this->hasOne(Header::class);
    }

    public function footer()
    {
        return $this->hasOne(Footer::class);
    }

    public function kategori()
    {
        return $this->hasMany(Kategori::class);
    }
}
