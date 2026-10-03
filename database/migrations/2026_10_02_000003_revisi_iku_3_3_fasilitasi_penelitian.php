<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABEL = 'capaian_fasilitasi_penelitian';
    // Dipakai untuk backfill sekaligus deteksi konflik (ekspresi harus identik).
    private const TANGGAL_BACKFILL = 'COALESCE(DATE(created_at), CURRENT_DATE)';

    /**
     * IKU 3.3: nidn/dosen_perwakilan/output dibuang, tanggal_kegiatan ditambah,
     * bukti dukung jadi opsional. Kunci unik baru sama seperti 3.1.
     */
        public function up(): void
    {
        // Berhenti SEBELUM mengubah skema apa pun bila unique baru pasti bentrok.
        $this->pastikanTidakAdaKonflikKunciBaru();

        Schema::table(self::TABEL, fn (Blueprint $t) => $t->date('tanggal_kegiatan')->nullable()->after('bentuk_fasilitasi'));
        DB::table(self::TABEL)->update(['tanggal_kegiatan' => DB::raw(self::TANGGAL_BACKFILL)]);

        Schema::table(self::TABEL, function (Blueprint $t) {
            $t->date('tanggal_kegiatan')->nullable(false)->change();
            $t->string('file_bukti_dukung')->nullable()->change();
        });

        // Unique baru DIBUAT DULU: di MySQL, unique lama adalah satu-satunya index
        // yang menopang FK capaian_kinerja_id, jadi tidak boleh di-drop sebelum ada penggantinya.
        Schema::table(self::TABEL, fn (Blueprint $t) => $t->unique(
            ['capaian_kinerja_id', 'pts_id', 'bentuk_fasilitasi', 'tanggal_kegiatan'],
            'ck_penelitian_fasilitasi_uq'
        ));

        Schema::table(self::TABEL, fn (Blueprint $t) => $t->dropUnique('ck_penelitian_dup_uq'));

        Schema::table(self::TABEL, fn (Blueprint $t) => $t->dropColumn(['nidn', 'dosen_perwakilan', 'output']));
    }

    // Catatan: isi nidn/dosen_perwakilan/output yang sudah dibuang TIDAK bisa dipulihkan
    // (kolom dibuat ulang kosong & nullable agar kompatibel SQLite).
    public function down(): void
    {
        Schema::table(self::TABEL, function (Blueprint $t) {
            $t->string('nidn', 20)->nullable();
            $t->string('dosen_perwakilan')->nullable();
            $t->text('output')->nullable();
        });

        // Sama seperti up(): unique pengganti dibuat dulu sebelum yang sekarang di-drop (alasan FK).
        Schema::table(self::TABEL, fn (Blueprint $t) => $t->unique(
            ['capaian_kinerja_id', 'pts_id', 'nidn', 'bentuk_fasilitasi'],
            'ck_penelitian_dup_uq'
        ));
        Schema::table(self::TABEL, fn (Blueprint $t) => $t->dropUnique('ck_penelitian_fasilitasi_uq'));

        DB::table(self::TABEL)->whereNull('file_bukti_dukung')->update(['file_bukti_dukung' => '']);
        Schema::table(self::TABEL, fn (Blueprint $t) => $t->string('file_bukti_dukung')->nullable(false)->change());

        Schema::table(self::TABEL, fn (Blueprint $t) => $t->dropColumn('tanggal_kegiatan'));
    }

    private function pastikanTidakAdaKonflikKunciBaru(): void
    {
        $konflik = DB::table(self::TABEL)
            ->select('capaian_kinerja_id', 'pts_id', 'bentuk_fasilitasi', DB::raw(self::TANGGAL_BACKFILL.' AS tanggal'), DB::raw('COUNT(*) AS jumlah'))
            ->groupBy('capaian_kinerja_id', 'pts_id', 'bentuk_fasilitasi', DB::raw(self::TANGGAL_BACKFILL))
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($konflik->isEmpty()) {
            return;
        }

        $contoh = $konflik->take(5)->map(fn ($k) => "capaian_kinerja_id={$k->capaian_kinerja_id}, pts_id={$k->pts_id}, bentuk=\"{$k->bentuk_fasilitasi}\", tanggal={$k->tanggal} ({$k->jumlah} baris)")->implode('; ');

        throw new RuntimeException("Migrasi dibatalkan: {$konflik->count()} kelompok baris akan bentrok pada unique baru (pts, bentuk, tanggal). Contoh: {$contoh}. Rapikan data tersebut lalu jalankan ulang. Tidak ada perubahan skema yang dilakukan.");
    }
};