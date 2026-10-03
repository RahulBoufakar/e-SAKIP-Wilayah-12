<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    private const TABEL = 'capaian_kebijakan_ppks';
    private const KOLOM_BARU = 'file_implementasi_ppks_antinarkoba_antikorupsi';

    /**
     * IKU 2.2: 3 dokumen wajib -> 1 dokumen wajib + file_bukti_dukung opsional.
     * Data lama: COALESCE(ppks, narkoba, korupsi). File fisik narkoba/korupsi
     * yang tidak lagi dirujuk baris mana pun dihapus dari disk 'private'.
     */
    public function up(): void
    {
        Schema::table(self::TABEL, fn (Blueprint $t) => $t->renameColumn('file_implementasi_ppks', self::KOLOM_BARU));

        DB::table(self::TABEL)->update([
            self::KOLOM_BARU => DB::raw('COALESCE('.self::KOLOM_BARU.', file_implementasi_anti_narkoba, file_implementasi_anti_korupsi)'),
        ]);

        // Catat file yang akan yatim SEBELUM kolomnya dibuang; hapus fisiknya setelah skema berubah.
        $baris = DB::table(self::TABEL)->get([self::KOLOM_BARU, 'file_implementasi_anti_narkoba', 'file_implementasi_anti_korupsi']);
        $dipakai = $baris->pluck(self::KOLOM_BARU)->filter()->all();
        $yatim = $baris->pluck('file_implementasi_anti_narkoba')
            ->merge($baris->pluck('file_implementasi_anti_korupsi'))
            ->filter()->unique()->diff($dipakai)->values()->all();

        Schema::table(self::TABEL, fn (Blueprint $t) => $t->dropColumn(['file_implementasi_anti_narkoba', 'file_implementasi_anti_korupsi']));
        Schema::table(self::TABEL, fn (Blueprint $t) => $t->string('file_bukti_dukung')->nullable());

        Storage::disk('private')->delete($yatim);
    }

    // Catatan: isi & file narkoba/korupsi yang sudah digabung/dihapus TIDAK bisa dipulihkan.
    public function down(): void
    {
        Schema::table(self::TABEL, function (Blueprint $t) {
            $t->string('file_implementasi_anti_narkoba')->nullable();
            $t->string('file_implementasi_anti_korupsi')->nullable();
        });
        Schema::table(self::TABEL, fn (Blueprint $t) => $t->dropColumn('file_bukti_dukung'));
        Schema::table(self::TABEL, fn (Blueprint $t) => $t->renameColumn(self::KOLOM_BARU, 'file_implementasi_ppks'));
    }
};