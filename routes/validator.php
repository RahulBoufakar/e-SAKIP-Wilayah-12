<?php

use App\Http\Controllers\Validator\DashboardController;
use App\Http\Controllers\Validator\DataProkerController;
use App\Http\Controllers\Validator\KalenderProkerController;
use App\Http\Controllers\Validator\UsulanProgramKerjaController;
use App\Http\Controllers\Validator\UsulanProgramKerjaFileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:validator'])
    ->prefix('validator')
    ->name('validator.')
    ->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('usulan-program-kerja', [UsulanProgramKerjaController::class, 'index'])->name('usulan-program-kerja.index');
        Route::get('usulan-program-kerja/{usulanProgramKerja}', [UsulanProgramKerjaController::class, 'show'])->name('usulan-program-kerja.show');
        Route::put('usulan-program-kerja/{usulanProgramKerja}/setujui', [UsulanProgramKerjaController::class, 'setujui'])->name('usulan-program-kerja.setujui');
        Route::put('usulan-program-kerja/{usulanProgramKerja}/tolak', [UsulanProgramKerjaController::class, 'tolak'])->name('usulan-program-kerja.tolak');

        Route::get('usulan-program-kerja/{usulanProgramKerja}/file/{field}/preview', [UsulanProgramKerjaFileController::class, 'preview'])->name('usulan-program-kerja.file.preview');
        Route::get('usulan-program-kerja/{usulanProgramKerja}/file/{field}/unduh', [UsulanProgramKerjaFileController::class, 'unduh'])->name('usulan-program-kerja.file.unduh');

        //Data Proker
        Route::get('data-proker', [DataProkerController::class, 'index'])->name('data-proker.index');
        Route::put('data-proker/detail-kegiatan/{detailKegiatan}/jenis-kegiatan', [DataProkerController::class, 'updateJenisKegiatan'])->name('data-proker.jenis-kegiatan.update');

        Route::get('kalender-proker', [KalenderProkerController::class, 'index'])->name('kalender-proker.index');
    });
    