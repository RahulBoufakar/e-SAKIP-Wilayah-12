<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bulan Kegiatan (checkbox per bulan, JSON [1..12]) diganti rentang tanggal_mulai - tanggal_selesai.
 * Data lama diisi dari bulan pertama s/d bulan terakhir yang dicentang pada tahun usulan, jadi
 * bulan yang tidak berurutan (mis. [8,10,11,12]) menjadi satu rentang penuh (Agu s/d Des).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detail_kegiatan', function (Blueprint $table) {
            $table->date('tanggal_mulai')->nullable()->after('bentuk_kegiatan');
            $table->date('tanggal_selesai')->nullable()->after('tanggal_mulai');
        });

        DB::table('detail_kegiatan')
            ->join('usulan_program_kerja', 'usulan_program_kerja.id', '=', 'detail_kegiatan.usulan_program_kerja_id')
            ->select('detail_kegiatan.id', 'detail_kegiatan.bulan_kegiatan', 'usulan_program_kerja.tahun')
            ->orderBy('detail_kegiatan.id')
            ->each(function ($row) {
                $bulan = array_map('intval', json_decode($row->bulan_kegiatan ?? '[]', true) ?: []);
                $bulan = array_filter($bulan, fn ($b) => $b >= 1 && $b <= 12);
                if (! $bulan) {
                    return;
                }

                DB::table('detail_kegiatan')->where('id', $row->id)->update([
                    'tanggal_mulai' => Carbon::create((int) $row->tahun, min($bulan), 1)->toDateString(),
                    'tanggal_selesai' => Carbon::create((int) $row->tahun, max($bulan), 1)->endOfMonth()->toDateString(),
                ]);
            });

        $this->dropKolom('bulan_kegiatan');
    }

    public function down(): void
    {
        Schema::table('detail_kegiatan', function (Blueprint $table) {
            $table->json('bulan_kegiatan')->nullable()->after('bentuk_kegiatan');
        });

        DB::table('detail_kegiatan')->whereNotNull('tanggal_mulai')->whereNotNull('tanggal_selesai')
            ->orderBy('id')
            ->each(function ($row) {
                $bulan = range(Carbon::parse($row->tanggal_mulai)->month, Carbon::parse($row->tanggal_selesai)->month);
                DB::table('detail_kegiatan')->where('id', $row->id)->update(['bulan_kegiatan' => json_encode($bulan)]);
            });

        $this->dropKolom('tanggal_mulai');
        $this->dropKolom('tanggal_selesai');
    }

    private function dropKolom(string $kolom): void
    {
        // Laravel 9 butuh doctrine/dbal untuk dropColumn di SQLite; SQLite >= 3.35 bisa langsung.
        if (DB::getDriverName() === 'sqlite') {
            DB::statement("ALTER TABLE detail_kegiatan DROP COLUMN {$kolom}");

            return;
        }

        Schema::table('detail_kegiatan', fn (Blueprint $table) => $table->dropColumn($kolom));
    }
};
