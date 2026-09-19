<?php

namespace App\Models;

use App\Http\Controllers\Concerns\GatesUsulanProgramKerja;
use App\Models\Concerns\LocksRowForTransition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use InvalidArgumentException;
use RuntimeException;

class UsulanProgramKerja extends Model
{
    use LocksRowForTransition;

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

    public function pts()
    {
        return $this->belongsToMany(Pts::class, 'usulan_program_kerja_pts');
    }

    /** Simpan sebagai draft (dipakai Tim Kerja saat masih bisa diedit). */
    public function simpan(array $data): static
    {
        $this->guardNotLocked();

        $this->fill($data);
        $this->save();

        return $this;
    }

    /**
     * Ajukan validasi: draft|rejected -> menunggu_validasi.
     * AUDIT § A5.1: dikunci lewat LocksRowForTransition (vocabulary
     * status_validasi/approved/rejected TETAP tidak berubah).
     */
    public function kirim(): static
    {
        $this->guardNotLocked();

        return $this->transitionWithLock(function ($fresh) {
            if (! in_array($fresh->status_validasi, ['draft', 'rejected'], true)) {
                throw new RuntimeException('Hanya usulan berstatus draft atau rejected yang bisa dikirim untuk validasi.');
            }

            $fresh->status_validasi = 'menunggu_validasi';
            $fresh->catatan_revisi = null;
            $fresh->save();

            $this->setRawAttributes($fresh->getAttributes(), true);

            return $this;
        });
    }

    /** Setujui: menunggu_validasi -> approved. Otomatis membuat baris program_kerja. */
    public function setujui(int $validatorId): static
    {
        return $this->transitionWithLock(function ($fresh) use ($validatorId) {
            if ($fresh->status_validasi !== 'menunggu_validasi') {
                throw new RuntimeException('Hanya usulan berstatus menunggu_validasi yang bisa disetujui.');
            }

            $fresh->status_validasi = 'approved';
            $fresh->validator_id = $validatorId;
            $fresh->tgl_validasi = now();
            $fresh->save();

            // AUDIT § A5.1: jaring pengaman kedua — kalau baris program_kerja
            // sudah lebih dulu ada (mis. sisa percobaan approve ganda dari
            // sebelum lock ini diterapkan), perlakukan sebagai no-op idempoten
            // alih-alih melempar exception tak tertangani, konsisten dengan
            // pola HandlesRestrictedDeletes yang sudah ada di codebase.
            try {
                $fresh->programKerja()->firstOrCreate([]);
            } catch (QueryException $e) {
                if ((int) $e->getCode() !== 23000) {
                    throw $e;
                }
            }

            $this->setRawAttributes($fresh->getAttributes(), true);

            return $this;
        });
    }

    /** Tolak: menunggu_validasi -> rejected. catatan_revisi wajib. */
    public function tolak(int $validatorId, string $catatanRevisi): static
    {
        if (trim($catatanRevisi) === '') {
            throw new InvalidArgumentException('Catatan revisi wajib diisi saat menolak usulan.');
        }

        return $this->transitionWithLock(function ($fresh) use ($validatorId, $catatanRevisi) {
            if ($fresh->status_validasi !== 'menunggu_validasi') {
                throw new RuntimeException('Hanya usulan berstatus menunggu_validasi yang bisa ditolak.');
            }

            $fresh->status_validasi = 'rejected';
            $fresh->validator_id = $validatorId;
            $fresh->tgl_validasi = now();
            $fresh->catatan_revisi = $catatanRevisi;
            $fresh->save();

            $this->setRawAttributes($fresh->getAttributes(), true);

            return $this;
        });
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
