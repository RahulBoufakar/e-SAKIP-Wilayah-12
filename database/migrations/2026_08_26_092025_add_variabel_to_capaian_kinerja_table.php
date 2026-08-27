<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('capaian_kinerja', function (Blueprint $table) {
            $table->json('variabel')->nullable()->after('realisasi');
        });
    }

    public function down(): void
    {
        Schema::table('capaian_kinerja', function (Blueprint $table) {
            $table->dropColumn('variabel');
        });
    }
};