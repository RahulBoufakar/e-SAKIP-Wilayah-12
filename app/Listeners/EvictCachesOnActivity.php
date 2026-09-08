<?php

namespace App\Listeners;

use App\Events\ActivityOccurred;
use App\Models\TahunAnggaran;
use App\Models\TimKerja;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;

/**
 * Satu titik terpusat untuk seluruh eviction cache aplikasi. Setiap perubahan
 * data pada modul Admin/Tim Kerja/Validator sudah mentrigger ActivityOccurred
 * (lihat masing-masing Controller), jadi listener ini menggantikan pemanggilan
 * Cache::forget() yang sebelumnya tersebar manual per-controller.
 *
 * Sengaja tidak menyimpan mapping "event mana -> forget key mana": cukup
 * forget seluruh dashboard/context cache pada setiap ActivityOccurred, karena
 * jumlah key yang di-forget kecil (dibatasi jumlah Tahun Anggaran x Tim Kerja)
 * dan mencegah kelupaan menambah forget baru saat modul baru ditambahkan.
 */
class EvictCachesOnActivity implements ShouldQueue
{
    public function handle(ActivityOccurred $event): void
    {
        // Context bar (dipakai composer navbar Admin/Tim Kerja/Validator)
        Cache::forget('context_tahun_list');

        $tahunIds = TahunAnggaran::pluck('id');

        foreach ($tahunIds as $tahunId) {
            Cache::forget("context_triwulan_aktif_{$tahunId}");

            // Dashboard Admin & Validator: dikunci per tahun anggaran saja.
            Cache::forget("admin_dashboard_v5_{$tahunId}");
            Cache::forget("validator_dashboard_v2_{$tahunId}");
        }

        // Dashboard Tim Kerja: dikunci per kombinasi tahun anggaran + tim kerja.
        $timKerjaIds = TimKerja::pluck('id')->sort()->values();

        if ($timKerjaIds->isNotEmpty()) {
            foreach ($tahunIds as $tahunId) {
                $this->forgetTimKerjaDashboardCombinations($tahunId, $timKerjaIds);
            }
        }
    }

    /**
     * Tim Kerja dashboard cache key memakai gabungan tim_kerja_id (implode '-'),
     * bukan hanya satu tim. Forget seluruh kombinasi tunggal per tim kerja
     * (kasus paling umum: satu user tergabung satu tim) plus kombinasi penuh
     * seluruh tim yang ada (jaring pengaman untuk user multi-tim).
     */
    private function forgetTimKerjaDashboardCombinations(int $tahunId, \Illuminate\Support\Collection $timKerjaIds): void
    {
        foreach ($timKerjaIds as $id) {
            Cache::forget("tim_kerja_dashboard_v3_{$tahunId}_{$id}");
        }

        Cache::forget('tim_kerja_dashboard_v3_'.$tahunId.'_'.$timKerjaIds->implode('-'));
    }
}