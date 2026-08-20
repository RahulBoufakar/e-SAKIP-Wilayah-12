<?php

use App\Http\Controllers\TimKerja\DashboardController;
use App\Http\Controllers\TimKerja\DataProkerController;
use App\Http\Controllers\TimKerja\DetailKegiatanController;
use App\Http\Controllers\TimKerja\IkuLldiktiController;
use App\Http\Controllers\TimKerja\RencanaAksiController;
use App\Http\Controllers\TimKerja\TargetKinerjaController;
use App\Http\Controllers\TimKerja\UsulanProgramKerjaController;
use App\Http\Controllers\TimKerja\UsulanProgramKerjaFileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:tim_kerja'])
    ->prefix('tim-kerja')
    ->name('tim-kerja.')
    ->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('target-kinerja', [TargetKinerjaController::class, 'index'])->name('target-kinerja.index');
        Route::get('rencana-aksi', [RencanaAksiController::class, 'index'])->name('rencana-aksi.index');
        Route::get('iku-lldikti', [IkuLldiktiController::class, 'index'])->name('iku-lldikti.index');

       // Usulan Program Kerja
        Route::get('usulan-program-kerja', [UsulanProgramKerjaController::class, 'index'])->name('usulan-program-kerja.index');
        Route::post('usulan-program-kerja', [UsulanProgramKerjaController::class, 'store'])->name('usulan-program-kerja.store');
        Route::get('usulan-program-kerja/{usulanProgramKerja}', [UsulanProgramKerjaController::class, 'show'])->name('usulan-program-kerja.show');
        Route::put('usulan-program-kerja/{usulanProgramKerja}', [UsulanProgramKerjaController::class, 'update'])->name('usulan-program-kerja.update');
        Route::put('usulan-program-kerja/{usulanProgramKerja}/kirim', [UsulanProgramKerjaController::class, 'kirim'])->name('usulan-program-kerja.kirim');
        Route::put('usulan-program-kerja/{usulanProgramKerja}/detail', [DetailKegiatanController::class, 'storeOrUpdate'])->name('usulan-program-kerja.detail.store-or-update');
        Route::get('usulan-program-kerja/{usulanProgramKerja}/file/{field}/preview', [UsulanProgramKerjaFileController::class, 'preview'])->name('usulan-program-kerja.file.preview');
        Route::get('usulan-program-kerja/{usulanProgramKerja}/file/{field}/unduh', [UsulanProgramKerjaFileController::class, 'unduh'])->name('usulan-program-kerja.file.unduh');
    
        // Data Proker
        Route::get('data-proker', [DataProkerController::class, 'index'])->name('data-proker.index');
    });