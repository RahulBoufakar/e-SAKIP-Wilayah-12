<?php

use App\Http\Controllers\Pimpinan\DashboardController;
use App\Http\Controllers\Pimpinan\LaporanKinerjaController;
use App\Http\Controllers\Pimpinan\ProgramKerja\KalenderProkerController;
use App\Http\Controllers\Pimpinan\TargetKinerja\IkuLldiktiController;
use App\Http\Controllers\Pimpinan\TargetKinerja\RencanaAksiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:pimpinan'])
    ->prefix('pimpinan')
    ->name('pimpinan.')
    ->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Target & Capaian Kinerja + Rencana Aksi Triwulan (read-only, lintas semua Tim Kerja)
        Route::get('iku-lldikti', [IkuLldiktiController::class, 'index'])->name('iku-lldikti.index');
        Route::get('rencana-aksi', [RencanaAksiController::class, 'index'])->name('rencana-aksi.index');

        // Program Kerja (read-only, lintas semua Tim Kerja)
        Route::get('kalender-proker', [KalenderProkerController::class, 'index'])->name('kalender-proker.index');

        // Laporan Kinerja
        Route::get('laporan', [LaporanKinerjaController::class, 'index'])->name('laporan.index');
        Route::post('laporan/generate', [LaporanKinerjaController::class, 'generate'])->name('laporan.generate');
        Route::get('laporan/status', [LaporanKinerjaController::class, 'status'])->name('laporan.status');
        Route::get('laporan/{laporanKinerja}/unduh', [LaporanKinerjaController::class, 'unduh'])->name('laporan.unduh');
    });
