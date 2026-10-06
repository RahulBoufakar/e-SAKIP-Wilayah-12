<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Peringatan hasil deteksi yang TIDAK bisa dihitung ulang dari data di database
    // (sheet dilewati, RO ganda, sub/akun yatim, ...). Bentuk: {"file": [...], "sheet": {"<kode_sheet>": [...]}}.
    // Peringatan struktur (nama kosong, kode ganda) dihitung langsung dari tabel supaya hilang saat admin memperbaikinya.
    public function up(): void
    {
        Schema::table('file_excel', function (Blueprint $table) {
            $table->json('peringatan')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('file_excel', function (Blueprint $table) {
            $table->dropColumn('peringatan');
        });
    }
};
