<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Snapshot akreditasi terkini per PTS (bukan riwayat). Data turunan -> cascade.
    public function up(): void
    {
        Schema::create('pts_akreditasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pts_id')->unique()->constrained('pts')->cascadeOnDelete();
            $table->string('peringkat', 50); // nilai asli API, mis. "Baik Sekali"
            $table->string('no_sk')->nullable();
            $table->date('tgl_sk')->nullable();
            $table->date('tgl_akhir')->nullable(); // -> masa_berlaku saat import capaian
            $table->string('status_sk', 30);
            $table->timestamp('terakhir_sinkron_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pts_akreditasi');
    }
};