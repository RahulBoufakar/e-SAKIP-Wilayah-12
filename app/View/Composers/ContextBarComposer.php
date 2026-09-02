<?php

namespace App\View\Composers;

use App\Models\TahunAnggaran;
use App\Models\TriwulanStatus;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class ContextBarComposer
{
    public function compose(View $view): void
    {
        // Cache daftar tahun (30 menit)
        $ctxTahunList = Cache::remember('context_tahun_list', 1800, function () {
            return TahunAnggaran::orderByDesc('tahun')->get(['id', 'tahun']);
        });

        // Ambil session atau default ke tahun pertama
        $ctxTahunAktifId = session('tahun_anggaran_id') ?? $ctxTahunList->first()?->id;

        // Cache triwulan aktif per tahun id (30 menit)
        $ctxTriwulanAktif = $ctxTahunAktifId
            ? Cache::remember("context_triwulan_aktif_{$ctxTahunAktifId}", 1800, function () use ($ctxTahunAktifId) {
                return TriwulanStatus::with('triwulan')
                    ->where('tahun_anggaran_id', $ctxTahunAktifId)
                    ->where('status', 'aktif')
                    ->first();
            })
            : null;
        $view->with([
            'ctxTahunList' => $ctxTahunList,
            'ctxTahunAktifId' => $ctxTahunAktifId,
            'ctxTriwulanAktif' => $ctxTriwulanAktif,
        ]);
    }
}