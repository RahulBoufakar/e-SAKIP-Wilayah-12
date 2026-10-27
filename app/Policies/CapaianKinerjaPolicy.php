<?php

namespace App\Policies;

use App\Models\CapaianKinerja;
use App\Models\Iku;
use App\Models\TriwulanStatus;
use App\Models\User;
use App\Services\TeamOwnershipService;
use Illuminate\Auth\Access\Response;

class CapaianKinerjaPolicy
{
    public function __construct(private TeamOwnershipService $teamOwnership)
    {
    }

    public function approve(User $user, CapaianKinerja $capaianKinerja): bool
    {
        return $user->hasAnyRole(['validator', 'admin', 'super_admin'])
            && $capaianKinerja->status === 'menunggu_validasi'
            && $capaianKinerja->isDataLengkap();
    }

    public function reject(User $user, CapaianKinerja $capaianKinerja): bool
    {
        return $user->hasAnyRole(['validator', 'admin', 'super_admin'])
            && $capaianKinerja->status === 'menunggu_validasi';
    }

    // Tim Kerja: halaman detail IKU (header belum tentu ada). Dipanggil sebagai
    // authorize('viewTimKerja', [CapaianKinerja::class, $iku]).
    public function viewTimKerja(User $user, Iku $iku): Response
    {
        if (! $this->owns($user, $iku)) {
            return Response::deny();
        }

        return $iku->tipe_iku ? Response::allow() : Response::denyAsNotFound();
    }

    // Tim Kerja: baca (preview kirim, bukti) — cukup pemilik IKU.
    public function view(User $user, CapaianKinerja $capaianKinerja): bool
    {
        return $this->owns($user, $capaianKinerja->iku);
    }

    // Tim Kerja: ubah data (tambah/ubah/hapus baris, kirim) — hanya di Triwulan Aktif.
    public function update(User $user, CapaianKinerja $capaianKinerja): Response
    {
        if (! $this->owns($user, $capaianKinerja->iku)) {
            return Response::deny();
        }

        return $this->triwulanAktif($capaianKinerja)
            ? Response::allow()
            : Response::deny('Data Capaian Kinerja hanya dapat diubah pada Triwulan yang sedang aktif.');
    }

    public function migrasi(User $user, CapaianKinerja $capaianKinerja): Response
    {
        $update = $this->update($user, $capaianKinerja);
        if ($update->denied()) {
            return $update;
        }

        return config("capaian_kinerja_tipe.{$capaianKinerja->iku->tipe_iku}.entri_tunggal")
            ? Response::denyWithStatus(422, 'IKU ini tidak mendukung migrasi triwulan.')
            : Response::allow();
    }

    public function updateJumlahPublikasi(User $user, CapaianKinerja $capaianKinerja): Response
    {
        $update = $this->update($user, $capaianKinerja);
        if ($update->denied()) {
            return $update;
        }

        return $capaianKinerja->iku->tipe_iku === 'fasilitasi_penelitian'
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    // Import akreditasi dari sinkronisasi: syarat update (pemilik + Triwulan aktif) + hanya IKU arsitektur_pts.
    public function importPddikti(User $user, CapaianKinerja $capaianKinerja): Response
    {
        $update = $this->update($user, $capaianKinerja);
        if ($update->denied()) {
            return $update;
        }

        return $capaianKinerja->iku->tipe_iku === 'arsitektur_pts'
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    // AUDIT § B3: kepemilikan tim didelegasikan ke TeamOwnershipService.
    private function owns(User $user, ?Iku $iku): bool
    {
        return $user->hasRole('tim_kerja') && $this->teamOwnership->ownsIku($user, $iku);
    }

    private function triwulanAktif(CapaianKinerja $capaianKinerja): bool
    {
        $status = TriwulanStatus::where('tahun_anggaran_id', $capaianKinerja->tahun_anggaran_id)
            ->where('status', 'aktif')->first();

        return $status && $status->triwulan_id === $capaianKinerja->triwulan_id;
    }
}
