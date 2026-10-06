<?php

use App\Http\Controllers\Admin\RabGenerator\RabMasterController;
use Illuminate\Support\Facades\Route;

// RAB Generator — halaman admin untuk file master. Di belakang config('rab_generator.enabled')
// (dicek di constructor controller supaya bisa diubah saat tes).
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin/rab-generator')
    ->name('admin.rab-generator.')
    ->group(function () {
        Route::get('/', [RabMasterController::class, 'index'])->name('index');
        Route::post('/', [RabMasterController::class, 'store'])->name('store');
        Route::put('sheet/{sheet}', [RabMasterController::class, 'updateSheet'])->name('sheet.update');
        Route::get('{fileExcel}', [RabMasterController::class, 'show'])->name('show');
        Route::put('{fileExcel}/aktifkan', [RabMasterController::class, 'aktifkan'])->name('aktifkan');
    });
