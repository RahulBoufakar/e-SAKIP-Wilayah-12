<?php

use App\Http\Controllers\TimKerja\RabGenerator\RabReportController;
use Illuminate\Support\Facades\Route;

// RAB Generator — pratinjau dan unduhan template untuk Tim Kerja. Di belakang
// config('rab_generator.enabled') (dicek di constructor controller supaya bisa diubah saat tes).
Route::middleware(['auth', 'role:tim_kerja'])
    ->prefix('tim-kerja/rab-generator')
    ->name('tim-kerja.rab-generator.')
    ->group(function () {
        Route::get('pohon', [RabReportController::class, 'pohon'])->name('pohon');

        // Tiap permintaan memakai ±30 MB dan ±0,5-1 s CPU (02): batasi per user.
        Route::post('preview', [RabReportController::class, 'preview'])->middleware('throttle:10,1')->name('preview');
        Route::get('unduh/{token}', [RabReportController::class, 'unduh'])->whereUuid('token')->name('unduh');
    });
