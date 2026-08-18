<?php

use App\Http\Controllers\TimKerja\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:tim_kerja'])
    ->prefix('tim-kerja')
    ->name('tim-kerja.')
    ->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    });