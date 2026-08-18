<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Context sesi untuk halaman role Tim Kerja: tahun anggaran aktif (reuse
 * ResolvesActiveTahunAnggaran) + tim_kerja_id milik user login (dari
 * tabel pivot user_tim_kerja).
 */
trait ResolvesTimKerjaSession
{
    use ResolvesActiveTahunAnggaran;

    /** Semua tim_kerja_id milik user yang sedang login. */
    protected function activeTimKerjaIds(): Collection
    {
        return Auth::user()?->timKerja()->pluck('tim_kerja.id') ?? collect();
    }

    /** tim_kerja_id utama (pertama). Null jika user tidak ditugaskan ke tim manapun. */
    protected function activeTimKerjaId(): ?int
    {
        return $this->activeTimKerjaIds()->first();
    }
}