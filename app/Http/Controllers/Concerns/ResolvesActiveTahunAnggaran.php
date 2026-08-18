<?php

namespace App\Http\Controllers\Concerns;

use App\Models\TahunAnggaran;
use Illuminate\Http\Request;
use Illuminate\View\View;

trait ResolvesActiveTahunAnggaran
{
    /**
     * Desain Sistem §2: context bar Tahun Anggaran tampil persisten di semua
     * halaman. Null berarti belum ada Tahun Anggaran sama sekali di sistem —
     * pemanggil WAJIB cek null lalu tampilkan missingTahunAnggaran(), bukan abort().
     */
    protected function activeTahunAnggaranId(Request $request): ?int
    {
        return $request->session()->get('tahun_anggaran_id')
            ?? TahunAnggaran::orderByDesc('tahun')->value('id');
    }

    /**
     * PRD §6.5 / API_Routes §6: graceful degradation — layout penuh tetap
     * tampil, hanya area konten diganti pesan ramah (bukan abort/exception).
     */
    protected function missingTahunAnggaran(?string $layout = null, ?string $backRoute = null): View
    {
        return view('admin.layout.app-error-content', [
            'errorMessage' => 'Data tahun anggaran belum tersedia. Silakan hubungi Administrator.',
            'layout' => $layout,
            'backRoute' => $backRoute,
        ]);
    }
}
