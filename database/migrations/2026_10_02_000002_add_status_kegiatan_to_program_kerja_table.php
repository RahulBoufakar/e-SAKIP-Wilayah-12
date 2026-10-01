<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('program_kerja', function (Blueprint $table) {
            $table->enum('status_kegiatan', ['Belum Dilaksanakan', 'Sedang Dilaksanakan', 'Selesai Dilaksanakan'])
                ->default('Belum Dilaksanakan')
                ->after('kode_proker');
        });
    }

    public function down(): void
    {
        Schema::table('program_kerja', function (Blueprint $table) {
            $table->dropColumn('status_kegiatan');
        });
    }
};
