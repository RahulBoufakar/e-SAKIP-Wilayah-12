<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE detail_kegiatan MODIFY bentuk_kegiatan ENUM('Luring', 'Daring') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE detail_kegiatan MODIFY bentuk_kegiatan VARCHAR(255) NOT NULL");
    }
};