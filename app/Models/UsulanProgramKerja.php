<?php

namespace App\Models;

use App\Http\Controllers\Concerns\GatesUsulanProgramKerja;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use RuntimeException;

class UsulanProgramKerja extends Model
{

    protected $table = 'usulan_program_kerja';
    protected $fillable = [
        'iku_id', 'nama_usulan', 'deskripsi', 'permasalahan','tahun',
        'file_kak_pdf', 'file_rab_pdf', 'file_rab_excel',
        'status_validasi', 'validator_id', 'tgl_validasi', 'catatan_revisi',
    ];

    protected $casts = [
        'tgl_validasi' => 'datetime',
    ];

    public function iku()
    {
        return $this->belongsTo(Iku::class);
    }

    public function validator()
    {
        return $this->belongsTo(User::class, 'validator_id');
    }

    public function programKerja()
    {
        return $this->hasOne(ProgramKerja::class);
    }

    public function detailKegiatan()
    {
        return $this->hasOne(DetailKegiatan::class, 'usulan_program_kerja_id');
    }

    /** Simpan sebagai draft (dipakai Tim Kerja saat masih bisa diedit). */
    public function simpan(array $data): static
    {
        $this->guardNotLocked();

        $this->fill($data);
        $this->save();

        return $this;
    }

    /** Ajukan validasi: draft|rejected -> menunggu_validasi. */
    public function kirim(): static
    {
        $this->guardNotLocked();

        if (! in_array($this->status_validasi, ['draft', 'rejected'], true)) {
            throw new RuntimeException('Hanya usulan berstatus draft atau rejected yang bisa dikirim untuk validasi.');
        }

        $this->status_validasi = 'menunggu_validasi';
        $this->catatan_revisi = null;
        $this->save();

        return $this;
    }

    /** Setujui: menunggu_validasi -> approved. Otomatis membuat baris program_kerja. */
    public function setujui(int $validatorId): static
    {
        if ($this->status_validasi !== 'menunggu_validasi') {
            throw new RuntimeException('Hanya usulan berstatus menunggu_validasi yang bisa disetujui.');
        }

        $this->status_validasi = 'approved';
        $this->validator_id = $validatorId;
        $this->tgl_validasi = now();
        $this->save();

        $this->programKerja()->firstOrCreate([]);

        return $this;
    }

    /** Tolak: menunggu_validasi -> rejected. catatan_revisi wajib. */
    public function tolak(int $validatorId, string $catatanRevisi): static
    {
        if (trim($catatanRevisi) === '') {
            throw new InvalidArgumentException('Catatan revisi wajib diisi saat menolak usulan.');
        }

        if ($this->status_validasi !== 'menunggu_validasi') {
            throw new RuntimeException('Hanya usulan berstatus menunggu_validasi yang bisa ditolak.');
        }

        $this->status_validasi = 'rejected';
        $this->validator_id = $validatorId;
        $this->tgl_validasi = now();
        $this->catatan_revisi = $catatanRevisi;
        $this->save();

        return $this;
    }

    /** True jika field harus read-only untuk Tim Kerja saat ini. */
    public function isFieldLocked(): bool
    {
        return in_array($this->status_validasi, ['menunggu_validasi', 'approved'], true);
    }

    protected function guardNotLocked(): void
    {
        if ($this->isFieldLocked()) {
            throw new RuntimeException('Usulan ini sedang terkunci dan tidak dapat diubah.');
        }
    }

    public function getCanKirimAttribute(): bool
    {
        $filesLengkap = filled($this->file_kak_pdf) && filled($this->file_rab_pdf) && filled($this->file_rab_excel);

        return $filesLengkap
            && $this->detailKegiatan()->exists()
            && in_array($this->status_validasi, ['draft', 'rejected'], true);
    }
}