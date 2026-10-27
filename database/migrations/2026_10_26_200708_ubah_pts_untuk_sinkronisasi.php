<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * TDD Sinkronisasi PTS §3 Migration 1. Pola SQLite/MySQL sama dengan
     * add_hybrid_to_bentuk_kegiatan (enum SQLite = string + CHECK).
     */
    public function up(): void
    {
        // status_pts: enum + non_aktif, nullable, tanpa default 'aktif'.
        // Dikerjakan SEBELUM kolom baru ditambah, supaya CHECK enum kolom baru
        // tidak hilang saat SQLite membangun ulang tabel.
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('pts', fn (Blueprint $t) => $t->string('status_pts')->nullable()->default(null)->change());
        } else {
            DB::statement("ALTER TABLE pts MODIFY status_pts ENUM('aktif','non_aktif','alih_bentuk','tutup','alih_kelola','pembinaan') NULL DEFAULT NULL");
        }

        Schema::table('pts', function (Blueprint $t) {
            $t->string('provinsi', 100)->nullable()->after('nama_pts');
            $t->enum('sumber_data', ['manual', 'lldikti12', 'pddikti'])->default('manual')->after('akreditasi_pts');
            $t->timestamp('terakhir_sinkron_at')->nullable()->after('sumber_data');
        });
    }

    public function down(): void
    {
        // Nilai baru tidak muat di enum lama -> petakan dulu.
        DB::table('pts')->where('status_pts', 'non_aktif')->update(['status_pts' => 'pembinaan']);
        DB::table('pts')->whereNull('status_pts')->update(['status_pts' => 'aktif']); // default lama

        foreach (['provinsi', 'sumber_data', 'terakhir_sinkron_at'] as $kolom) {
            Schema::table('pts', fn (Blueprint $t) => $t->dropColumn($kolom));
        }

        if (DB::getDriverName() === 'sqlite') {
            Schema::table('pts', fn (Blueprint $t) => $t->string('status_pts')->default('aktif')->nullable(false)->change());
        } else {
            DB::statement("ALTER TABLE pts MODIFY status_pts ENUM('aktif','alih_bentuk','tutup','alih_kelola','pembinaan') NOT NULL DEFAULT 'aktif'");
        }
    }
};