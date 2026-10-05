<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABEL = 'capaian_dosen_naik_jafung';

    /**
     * IKU 3.2: dosen yang pertama kali naik jabatan fungsional.
     * jenjang_asal  + 'tenaga_pengajar'
     * jenjang_baru  + 'asisten_ahli'
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // Enum di SQLite = string + CHECK constraint; change() membangun ulang tabel tanpa constraint itu.
            Schema::table(self::TABEL, function (Blueprint $table) {
                $table->string('jenjang_asal')->change();
                $table->string('jenjang_baru')->change();
            });

            return;
        }

        DB::statement("ALTER TABLE ".self::TABEL." MODIFY jenjang_asal ENUM('tenaga_pengajar','asisten_ahli','lektor','lektor_kepala') NOT NULL");
        DB::statement("ALTER TABLE ".self::TABEL." MODIFY jenjang_baru ENUM('asisten_ahli','lektor','lektor_kepala','profesor') NOT NULL");
    }

    // Baris dengan nilai baru tidak muat di enum lama, jadi dipetakan ke nilai terdekat dulu.
    public function down(): void
    {
        DB::table(self::TABEL)->where('jenjang_asal', 'tenaga_pengajar')->update(['jenjang_asal' => 'asisten_ahli']);
        DB::table(self::TABEL)->where('jenjang_baru', 'asisten_ahli')->update(['jenjang_baru' => 'lektor']);

        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE ".self::TABEL." MODIFY jenjang_asal ENUM('asisten_ahli','lektor','lektor_kepala') NOT NULL");
        DB::statement("ALTER TABLE ".self::TABEL." MODIFY jenjang_baru ENUM('lektor','lektor_kepala','profesor') NOT NULL");
    }
};
