<?php

use App\Http\Controllers\LandingController;
use Illuminate\Support\Facades\Route;

// GET / (FR-L1): pengganti welcome page Laravel default
Route::get('/', [LandingController::class, 'index'])->name('landing');

// Placeholder Tim Kerja/Validator (jawaban Open Question #1 — belum ada
// auth, tombol Landing Page cukup diarahkan ke halaman "segera hadir").
// Tim Kerja/Validator sendiri di luar scope PRD §3.
Route::view('/tim-kerja', 'placeholder.index', ['role' => 'Tim Kerja'])->name('tim-kerja.placeholder');
Route::view('/validator', 'placeholder.index', ['role' => 'Validator'])->name('validator.placeholder');

require __DIR__.'/admin.php';
