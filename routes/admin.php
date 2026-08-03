<?php

use App\Http\Controllers\Admin\ContextController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\IkuController;
use App\Http\Controllers\Admin\MasterData\TimKerjaController;
use App\Http\Controllers\Admin\MasterData\UserController;
use App\Http\Controllers\Admin\RealisasiController;
use App\Http\Controllers\Admin\RencanaAksiController;
use App\Http\Controllers\Admin\SasaranKegiatanController;
use App\Http\Controllers\Admin\Tools\JumlahMahasiswaController;
use App\Http\Controllers\Admin\Tools\JumlahPtsController;
use App\Http\Controllers\Admin\Tools\SinkronisasiController;
use App\Http\Controllers\Admin\Tools\TahunAnggaranController;
use App\Http\Controllers\Admin\Tools\TriwulanController;
use Illuminate\Support\Facades\Route;

// Semua route di bawah middleware auth + role:administrator, prefix /admin
// (autentikasi/otorisasi ini di luar scope PRD §3 — diasumsikan sudah ada / dibuat menyusul).
//middleware(['auth', 'role:administrator'])
Route::prefix('admin')
    ->name('admin.')
    ->group(function () {

        // Dashboard (FR-D1/FR-D2)
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Context bar Tahun Anggaran (lihat ContextController — tidak eksplisit di API_Routes doc)
        Route::post('context/tahun-anggaran', [ContextController::class, 'setTahunAnggaran'])
            ->name('context.tahun-anggaran');

        // Master Data
        Route::prefix('master-data')->name('master-data.')->group(function () {
            Route::get('tim-kerja', [TimKerjaController::class, 'index'])->name('tim-kerja.index');
            Route::post('tim-kerja', [TimKerjaController::class, 'store'])->name('tim-kerja.store');
            Route::put('tim-kerja/{timKerja}', [TimKerjaController::class, 'update'])->name('tim-kerja.update');
            Route::delete('tim-kerja/{timKerja}', [TimKerjaController::class, 'destroy'])->name('tim-kerja.destroy');

            Route::get('user', [UserController::class, 'index'])->name('user.index');
            Route::post('user', [UserController::class, 'store'])->name('user.store');
            Route::put('user/{user}', [UserController::class, 'update'])->name('user.update');
            Route::delete('user/{user}', [UserController::class, 'destroy'])->name('user.destroy');
        });

        // 1. Target Kinerja (Sasaran Kegiatan)
        Route::get('target-kinerja', [SasaranKegiatanController::class, 'index'])->name('target-kinerja.index');
        Route::post('target-kinerja', [SasaranKegiatanController::class, 'store'])->name('target-kinerja.store');
        Route::put('target-kinerja/{sasaran}', [SasaranKegiatanController::class, 'update'])->name('target-kinerja.update');
        Route::delete('target-kinerja/{sasaran}', [SasaranKegiatanController::class, 'destroy'])->name('target-kinerja.destroy');
        Route::get('target-kinerja/{sasaran}', [SasaranKegiatanController::class, 'show'])->name('target-kinerja.show');

        // 2. Menu "iku" — tempat create IKU/IKK
        Route::post('iku', [IkuController::class, 'store'])->name('iku.store');
        Route::put('iku/{iku}', [IkuController::class, 'update'])->name('iku.update');
        Route::delete('iku/{iku}', [IkuController::class, 'destroy'])->name('iku.destroy');

        // 3. Menu "iku-lldikti" — tampilan gabungan, baca + edit + hapus data yang sudah ada
        Route::get('iku-lldikti', [RealisasiController::class, 'index'])->name('iku-lldikti.index');
        Route::put('realisasi', [RealisasiController::class, 'storeOrUpdate'])->name('realisasi.store-or-update');

        // 4. Rencana Aksi Triwulan (GET /rencana-aksi/{iku} pre-fill DIHAPUS, lihat controller)
        Route::get('rencana-aksi', [RencanaAksiController::class, 'index'])->name('rencana-aksi.index');
        Route::put('rencana-aksi/{iku}', [RencanaAksiController::class, 'update'])->name('rencana-aksi.update');

        // 5. Tools
        Route::prefix('tools')->name('tools.')->group(function () {
            Route::get('triwulan', [TriwulanController::class, 'index'])->name('triwulan.index');
            Route::put('triwulan/{triwulan}', [TriwulanController::class, 'update'])->name('triwulan.update');
            Route::put('triwulan/nonaktifkan-semua/{tahunAnggaranId}', [TriwulanController::class, 'nonaktifkanSemua'])
            ->name('triwulan.nonaktifkan-semua');

            Route::get('tahun', [TahunAnggaranController::class, 'index'])->name('tahun.index');
            Route::post('tahun', [TahunAnggaranController::class, 'store'])->name('tahun.store');
            Route::delete('tahun/{tahun}', [TahunAnggaranController::class, 'destroy'])->name('tahun.destroy');

            Route::get('jumlah-mahasiswa', [JumlahMahasiswaController::class, 'index'])->name('jumlah-mahasiswa.index');
            Route::post('jumlah-mahasiswa', [JumlahMahasiswaController::class, 'store'])->name('jumlah-mahasiswa.store');
            Route::delete('jumlah-mahasiswa/{jumlahMahasiswa}', [JumlahMahasiswaController::class, 'destroy'])->name('jumlah-mahasiswa.destroy');

            Route::get('jumlah-pts', [JumlahPtsController::class, 'index'])->name('jumlah-pts.index');
            Route::post('jumlah-pts', [JumlahPtsController::class, 'store'])->name('jumlah-pts.store');
            Route::delete('jumlah-pts/{jumlahPts}', [JumlahPtsController::class, 'destroy'])->name('jumlah-pts.destroy');

            // FR-34: hanya halaman placeholder, sengaja tidak ada route POST aktif
            Route::get('sinkronisasi', [SinkronisasiController::class, 'index'])->name('sinkronisasi.index');
        });
    });
