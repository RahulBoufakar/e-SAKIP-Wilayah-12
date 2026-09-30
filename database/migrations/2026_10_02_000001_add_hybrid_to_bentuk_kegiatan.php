<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // Enum di SQLite = string + CHECK constraint; change() membangun ulang tabel tanpa constraint itu.
            Schema::table('detail_kegiatan', function (Blueprint $table) {
                $table->string('bentuk_kegiatan')->change();
            });

            return;
        }

        DB::statement("ALTER TABLE detail_kegiatan MODIFY bentuk_kegiatan ENUM('Luring','Daring','Hybrid') NOT NULL");
    }

    public function down(): void
    {
        // Baris Hybrid tidak muat di enum lama, jadi dikembalikan ke Luring dulu.
        DB::table('detail_kegiatan')->where('bentuk_kegiatan', 'Hybrid')->update(['bentuk_kegiatan' => 'Luring']);

        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE detail_kegiatan MODIFY bentuk_kegiatan ENUM('Luring','Daring') NOT NULL");
    }
};
