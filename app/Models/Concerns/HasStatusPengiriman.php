<?php

namespace App\Models\Concerns;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use RuntimeException;

/**
 * State machine status pengiriman untuk modul Tim Kerja:
 *   draft -> menunggu_validasi -> disetujui
 *                               -> ditolak -> (revisi, kirim() lagi)
 *
 * Model pemakai WAJIB punya kolom: status, catatan_revisi.
 * Dipakai oleh: UsulanProgramKerja, PelaporanKegiatan, CapaianKinerja, AnalisaKinerja.
 *
 * AUDIT § A5.1: transisi status (kirim/setujui/tolak) dikunci lewat
 * LocksRowForTransition untuk mencegah race condition pada pola
 * "baca status -> cek -> tulis" yang sebelumnya tidak atomik.
 */
trait HasStatusPengiriman
{
    use LocksRowForTransition;

    /** Simpan sebagai draft. Gagal jika field sedang terkunci (lihat isFieldLocked()). */
    public function simpan(array $data): static
    {
        $this->guardNotLocked();

        $this->fill($data);
        $this->save();

        return $this;
    }

    /** Ajukan validasi: draft|ditolak -> menunggu_validasi. */
    public function kirim(): static
    {
        $this->guardNotLocked();

        return $this->transitionWithLock(function ($fresh) {
            if (! in_array($fresh->status, ['draft', 'ditolak'], true)) {
                throw new RuntimeException('Hanya data berstatus draft atau ditolak yang bisa dikirim untuk validasi.');
            }

            $fresh->status = 'menunggu_validasi';
            $fresh->catatan_revisi = null;
            $fresh->save();

            $this->setRawAttributes($fresh->getAttributes(), true);

            return $this;
        });
    }

    /** Setujui: menunggu_validasi -> disetujui. Khusus role validator/admin/super_admin. */
    public function setujui(): static
    {
        $this->guardRole(['validator', 'admin', 'super_admin']);

        return $this->transitionWithLock(function ($fresh) {
            if ($fresh->status !== 'menunggu_validasi') {
                throw new RuntimeException('Hanya data berstatus menunggu_validasi yang bisa disetujui.');
            }

            $fresh->status = 'disetujui';
            $fresh->save();

            $this->setRawAttributes($fresh->getAttributes(), true);

            return $this;
        });
    }

    /** Tolak: menunggu_validasi -> ditolak. Khusus role validator/admin/super_admin. catatan_revisi wajib. */
    public function tolak(string $catatanRevisi): static
    {
        $this->guardRole(['validator', 'admin', 'super_admin']);

        if (trim($catatanRevisi) === '') {
            throw new InvalidArgumentException('Catatan revisi wajib diisi saat menolak pengajuan.');
        }

        return $this->transitionWithLock(function ($fresh) use ($catatanRevisi) {
            if ($fresh->status !== 'menunggu_validasi') {
                throw new RuntimeException('Hanya data berstatus menunggu_validasi yang bisa ditolak.');
            }

            $fresh->status = 'ditolak';
            $fresh->catatan_revisi = $catatanRevisi;
            $fresh->save();

            $this->setRawAttributes($fresh->getAttributes(), true);

            return $this;
        });
    }

    /**
     * True jika field harus read-only untuk user saat ini.
     * - menunggu_validasi: terkunci untuk semua role.
     * - disetujui: terkunci kecuali role super_admin.
     * - draft/ditolak: tidak terkunci oleh status (masih tunduk pada isTriwulanAktif() di sisi halaman).
     */
    public function isFieldLocked(): bool
    {
        if ($this->status === 'menunggu_validasi') {
            return true;
        }

        if ($this->status === 'disetujui') {
            return ! (Auth::user()?->hasRole('super_admin') ?? false);
        }

        return false;
    }

    protected function guardNotLocked(): void
    {
        if ($this->isFieldLocked()) {
            throw new RuntimeException('Data ini sedang terkunci dan tidak dapat diubah.');
        }
    }

    protected function guardRole(array $roles): void
    {
        $user = Auth::user();

        if (! $user || ! $user->hasAnyRole($roles)) {
            throw new AuthorizationException('Anda tidak memiliki izin untuk aksi ini.');
        }
    }
}
