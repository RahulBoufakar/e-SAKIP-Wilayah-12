<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABEL = 'capaian_akreditasi_pts';

    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table(self::TABEL, fn (Blueprint $t) => $t->string('sumber')->default('manual')->change());

            return;
        }

        DB::statement('ALTER TABLE '.self::TABEL." MODIFY sumber ENUM('manual','lldikti12','pddikti') NOT NULL DEFAULT 'manual'");
    }

    public function down(): void
    {
        DB::table(self::TABEL)->where('sumber', 'lldikti12')->update(['sumber' => 'pddikti']);

        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE '.self::TABEL." MODIFY sumber ENUM('manual','pddikti') NOT NULL DEFAULT 'manual'");
    }
};