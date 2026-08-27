<?php

use App\Http\Controllers\TimKerja\CapaianKinerjaController;
use App\Http\Controllers\TimKerja\CapaianKinerjaDokumenController;
use App\Http\Controllers\TimKerja\DashboardController;
use App\Http\Controllers\TimKerja\DataProkerController;
use App\Http\Controllers\TimKerja\DetailKegiatanController;
use App\Http\Controllers\TimKerja\DokumenLaporanKegiatanFileController;
use App\Http\Controllers\TimKerja\IkuLldiktiController;
use App\Http\Controllers\TimKerja\KalenderProkerController;
use App\Http\Controllers\TimKerja\PelaporanKegiatanController;
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

        // Kalender Proker
        Route::get('kalender-proker', [KalenderProkerController::class, 'index'])->name('kalender-proker.index');

        // Pelaporan Kegiatan
        Route::get('pelaporan-kegiatan', [PelaporanKegiatanController::class, 'index'])->name('pelaporan-kegiatan.index');
        Route::get('pelaporan-kegiatan/{programKerja}', [PelaporanKegiatanController::class, 'show'])->name('pelaporan-kegiatan.show');
        Route::post('pelaporan-kegiatan/{laporanKegiatan}/dokumen', [PelaporanKegiatanController::class, 'storeDokumen'])->name('pelaporan-kegiatan.dokumen.store');
        Route::put('pelaporan-kegiatan/dokumen/{dokumenLaporanKegiatan}/upload', [PelaporanKegiatanController::class, 'uploadDokumen'])->name('pelaporan-kegiatan.dokumen.upload');
        Route::get('pelaporan-kegiatan/dokumen/{dokumenLaporanKegiatan}/preview', [DokumenLaporanKegiatanFileController::class, 'preview'])->name('pelaporan-kegiatan.dokumen.preview');
        Route::get('pelaporan-kegiatan/dokumen/{dokumenLaporanKegiatan}/unduh', [DokumenLaporanKegiatanFileController::class, 'unduh'])->name('pelaporan-kegiatan.dokumen.unduh');

        // Capaian Kinerja
        Route::get('capaian-kinerja', [CapaianKinerjaController::class, 'index'])->name('capaian-kinerja.index');
        Route::get('capaian-kinerja/{iku}/{triwulan}', [CapaianKinerjaController::class, 'show'])->name('capaian-kinerja.show');
        Route::put('capaian-kinerja/{capaianKinerja}/kirim', [CapaianKinerjaController::class, 'kirim'])->name('capaian-kinerja.kirim');
        Route::put('capaian-kinerja/{iku}/{triwulan}', [CapaianKinerjaController::class, 'update'])->name('capaian-kinerja.update');
        Route::post('capaian-kinerja/{capaianKinerja}/dokumen', [CapaianKinerjaDokumenController::class, 'store'])->name('capaian-kinerja.dokumen.store');
        Route::delete('capaian-kinerja/dokumen/{dokumen}', [CapaianKinerjaDokumenController::class, 'destroy'])->name('capaian-kinerja.dokumen.destroy');
        Route::get('capaian-kinerja/dokumen/{dokumen}/preview', [CapaianKinerjaDokumenController::class, 'preview'])->name('capaian-kinerja.dokumen.preview');
        Route::get('capaian-kinerja/dokumen/{dokumen}/unduh', [CapaianKinerjaDokumenController::class, 'unduh'])->name('capaian-kinerja.dokumen.unduh');
    });