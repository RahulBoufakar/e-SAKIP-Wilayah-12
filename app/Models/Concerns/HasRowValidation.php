<?php

namespace App\Models\Concerns;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use RuntimeException;

/**
 * Spek Capaian Kinerja Hybrid §7 — state machine status_validasi untuk
 * BARIS DETAIL (10 tabel §4.3): draft -> menunggu_validasi -> disetujui
 *                                                            -> ditolak -> (revisi) -> menunggu_validasi
 *
 * Sengaja terpisah dari App\Models\Concerns\HasStatusPengiriman (kolom
 * `status`, dipakai header/modul lain seperti UsulanProgramKerja): trait ini
 * memakai kolom `status_validasi` sesuai skema tabel detail, dan tidak
 * mengelola status agregat header — itu tanggung jawab
 * CapaianKinerja::syncStatusFromBaris(), dipanggil controller setelah baris
 * berubah.
 *
 * Menggunakan ulang LocksRowForTransition (sudah ada di codebase) untuk
 * row-lock atomik, konsisten dengan pola yang sudah dipakai
 * UsulanProgramKerja/PelaporanKegiatan/CapaianKinerja lama.
 */
trait HasRowValidation
{
    use LocksRowForTransition;

    public function scopeDisetujui($query)
    {
        return $query->where('status_validasi', 'disetujui');
    }

    public function scopeMenungguValidasi($query)
    {
        return $query->where('status_validasi', 'menunggu_validasi');
    }

    /** draft|ditolak -> menunggu_validasi. */
    public function kirim(): static
    {
        $this->guardNotLocked();

        return $this->transitionWithLock(function ($fresh) {
            if (! in_array($fresh->status_validasi, ['draft', 'ditolak'], true)) {
                throw new RuntimeException('Hanya baris berstatus draft atau ditolak yang bisa dikirim untuk validasi.');
            }

            $fresh->status_validasi = 'menunggu_validasi';
            $fresh->catatan_revisi = null;
            $fresh->save();

            $this->setRawAttributes($fresh->getAttributes(), true);

            return $this;
        });
    }

    /** menunggu_validasi -> disetujui. Khusus role validator/admin/super_admin. */
    public function setujui(): static
    {
        $this->guardRole(['validator', 'admin', 'super_admin']);

        return $this->transitionWithLock(function ($fresh) {
            if ($fresh->status_validasi !== 'menunggu_validasi') {
                throw new RuntimeException('Hanya baris berstatus menunggu_validasi yang bisa disetujui.');
            }

            $fresh->status_validasi = 'disetujui';
            $fresh->save();

            $this->setRawAttributes($fresh->getAttributes(), true);

            return $this;
        });
    }

    /** menunggu_validasi -> ditolak. catatan_revisi wajib. */
    public function tolak(string $catatanRevisi): static
    {
        $this->guardRole(['validator', 'admin', 'super_admin']);

        if (trim($catatanRevisi) === '') {
            throw new InvalidArgumentException('Catatan revisi wajib diisi saat menolak baris.');
        }

        return $this->transitionWithLock(function ($fresh) use ($catatanRevisi) {
            if ($fresh->status_validasi !== 'menunggu_validasi') {
                throw new RuntimeException('Hanya baris berstatus menunggu_validasi yang bisa ditolak.');
            }

            $fresh->status_validasi = 'ditolak';
            $fresh->catatan_revisi = $catatanRevisi;
            $fresh->save();

            $this->setRawAttributes($fresh->getAttributes(), true);

            return $this;
        });
    }

    /** Terkunci utk Tim Kerja saat menunggu_validasi, atau disetujui (kecuali super_admin). */
    public function isFieldLocked(): bool
    {
        if ($this->status_validasi === 'menunggu_validasi') {
            return true;
        }

        if ($this->status_validasi === 'disetujui') {
            return ! (Auth::user()?->hasRole('super_admin') ?? false);
        }

        return false;
    }

    protected function guardNotLocked(): void
    {
        if ($this->isFieldLocked()) {
            throw new RuntimeException('Baris ini sedang terkunci dan tidak dapat diubah.');
        }
    }

    protected function guardRole(array $roles): void
    {
        $user = Auth::user();

        if (! $user || ! $user->hasAnyRole($roles)) {
            throw new AuthorizationException('Anda tidak memiliki izin untuk aksi ini.');
        }
    }

    public function scopeStatusIn($query, array $statuses)
    {
        return $query->whereIn('status_validasi', $statuses);
    }
}
