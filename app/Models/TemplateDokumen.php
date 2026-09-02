<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class TemplateDokumen extends Model
{
    const CREATED_AT = null;

    private const FORMAT = [
        'rab_excel' => ['mimes' => 'xls,xlsx', 'accept' => '.xls,.xlsx', 'label' => 'Excel (XLS/XLSX)'],
        'rab_pdf' => ['mimes' => 'pdf', 'accept' => '.pdf', 'label' => 'PDF'],
        'kak_tor' => ['mimes' => 'doc,docx', 'accept' => '.doc,.docx', 'label' => 'Word (DOC/DOCX)'],
    ];

    protected $table = 'template_dokumen';
    protected $fillable = ['kode', 'nama', 'file'];

    public function getFileUrlAttribute(): ?string
    {
        return $this->file ? Storage::url($this->file) : null;
    }

    public function validationMimes(): string
    {
        return self::FORMAT[$this->kode]['mimes'];
    }

    public function acceptAttribute(): string
    {
        return self::FORMAT[$this->kode]['accept'];
    }

    public function formatLabel(): string
    {
        return self::FORMAT[$this->kode]['label'];
    }

    /** Hanya format PDF yang didukung pratinjau (iframe). */
    public function isPdf(): bool
    {
        return $this->kode === 'rab_pdf';
    }
}