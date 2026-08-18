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
 */
trait HasStatusPengiriman
{
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

        if (! in_array($this->status, ['draft', 'ditolak'], true)) {
            throw new RuntimeException('Hanya data berstatus draft atau ditolak yang bisa dikirim untuk validasi.');
        }

        $this->status = 'menunggu_validasi';
        $this->catatan_revisi = null;
        $this->save();

        return $this;
    }

    /** Setujui: menunggu_validasi -> disetujui. Khusus role validator/admin/super_admin. */
    public function setujui(): static
    {
        $this->guardRole(['validator', 'admin', 'super_admin']);

        if ($this->status !== 'menunggu_validasi') {
            throw new RuntimeException('Hanya data berstatus menunggu_validasi yang bisa disetujui.');
        }

        $this->status = 'disetujui';
        $this->save();

        return $this;
    }

    /** Tolak: menunggu_validasi -> ditolak. Khusus role validator/admin/super_admin. catatan_revisi wajib. */
    public function tolak(string $catatanRevisi): static
    {
        $this->guardRole(['validator', 'admin', 'super_admin']);

        if (trim($catatanRevisi) === '') {
            throw new InvalidArgumentException('Catatan revisi wajib diisi saat menolak pengajuan.');
        }

        if ($this->status !== 'menunggu_validasi') {
            throw new RuntimeException('Hanya data berstatus menunggu_validasi yang bisa ditolak.');
        }

        $this->status = 'ditolak';
        $this->catatan_revisi = $catatanRevisi;
        $this->save();

        return $this;
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
