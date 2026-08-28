<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('analisa_kinerja', function (Blueprint $table) {
            $table->text('progress')->nullable()->after('tahun_anggaran_id');
            $table->text('kendala')->nullable()->after('progress');
            $table->text('tindak_lanjut')->nullable()->after('kendala');
        });
    }

    public function down(): void
    {
        Schema::table('analisa_kinerja', function (Blueprint $table) {
            $table->dropColumn(['progress', 'kendala', 'tindak_lanjut']);
        });
    }
};