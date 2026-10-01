<?php

use App\Http\Controllers\TimKerja\CapaianKinerja\AnalisaKinerjaController;
use App\Http\Controllers\TimKerja\CapaianKinerja\CapaianKinerjaController;
use App\Http\Controllers\TimKerja\CapaianKinerja\CapaianKinerjaDokumenController;
use App\Http\Controllers\TimKerja\DashboardController;
use App\Http\Controllers\TimKerja\ProgramKerja\DataProkerController;
use App\Http\Controllers\TimKerja\ProgramKerja\DetailKegiatanController;
use App\Http\Controllers\TimKerja\ProgramKerja\DokumenLaporanKegiatanFileController;
use App\Http\Controllers\TimKerja\ProgramKerja\KalenderProkerController;
use App\Http\Controllers\TimKerja\ProgramKerja\PelaporanKegiatanController;
use App\Http\Controllers\TimKerja\ProgramKerja\PtsTaggingController;
use App\Http\Controllers\TimKerja\ProgramKerja\UsulanProgramKerjaController;
use App\Http\Controllers\TimKerja\ProgramKerja\UsulanProgramKerjaFileController;
use App\Http\Controllers\TimKerja\TargetKinerja\IkuLldiktiController;
use App\Http\Controllers\TimKerja\TargetKinerja\RencanaAksiController;
use App\Http\Controllers\TimKerja\TargetKinerja\TargetKinerjaController;
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
        Route::put('data-proker/{usulanProgramKerja}/tag-pts', [PtsTaggingController::class, 'storeOrUpdate'])->name('data-proker.tag-pts');

        // Kalender Proker
        Route::get('kalender-proker', [KalenderProkerController::class, 'index'])->name('kalender-proker.index');

        // Pelaporan Kegiatan
        Route::get('pelaporan-kegiatan', [PelaporanKegiatanController::class, 'index'])->name('pelaporan-kegiatan.index');
        Route::get('pelaporan-kegiatan/{programKerja}', [PelaporanKegiatanController::class, 'show'])->name('pelaporan-kegiatan.show');
        Route::post('pelaporan-kegiatan/{laporanKegiatan}/dokumen', [PelaporanKegiatanController::class, 'storeDokumen'])->name('pelaporan-kegiatan.dokumen.store');
        Route::put('pelaporan-kegiatan/dokumen/{dokumenLaporanKegiatan}/upload', [PelaporanKegiatanController::class, 'uploadDokumen'])->name('pelaporan-kegiatan.dokumen.upload');
        Route::delete('pelaporan-kegiatan/dokumen/{dokumenLaporanKegiatan}', [PelaporanKegiatanController::class, 'destroyDokumen'])->name('pelaporan-kegiatan.dokumen.destroy');
        Route::get('pelaporan-kegiatan/dokumen/{dokumenLaporanKegiatan}/preview', [DokumenLaporanKegiatanFileController::class, 'preview'])->name('pelaporan-kegiatan.dokumen.preview');
        Route::get('pelaporan-kegiatan/dokumen/{dokumenLaporanKegiatan}/unduh', [DokumenLaporanKegiatanFileController::class, 'unduh'])->name('pelaporan-kegiatan.dokumen.unduh');

        // Capaian Kinerja
        Route::get('capaian-kinerja', [CapaianKinerjaController::class, 'index'])->name('capaian-kinerja.index');
        Route::get('capaian-kinerja/{iku}', [CapaianKinerjaController::class, 'show'])->name('capaian-kinerja.show');
        Route::get('capaian-kinerja/{iku}/preview-kirim', [CapaianKinerjaController::class, 'previewKirim'])->name('capaian-kinerja.preview-kirim');
        Route::put('capaian-kinerja/{iku}/kirim', [CapaianKinerjaController::class, 'kirim'])->name('capaian-kinerja.kirim');
        Route::post('capaian-kinerja/{iku}/baris/{komponen}', [CapaianKinerjaController::class, 'storeBaris'])->name('capaian-kinerja.baris.store');
        Route::put('capaian-kinerja/{iku}/baris/{komponen}/{barisId}', [CapaianKinerjaController::class, 'updateBaris'])->name('capaian-kinerja.baris.update');
        Route::delete('capaian-kinerja/{iku}/baris/{komponen}/{barisId}', [CapaianKinerjaController::class, 'destroyBaris'])->name('capaian-kinerja.baris.destroy');
        Route::get('capaian-kinerja/{iku}/bukti/{komponen}/{barisId}/preview', [CapaianKinerjaController::class, 'previewBukti'])->name('capaian-kinerja.bukti.preview');
        Route::get('capaian-kinerja/{iku}/bukti/{komponen}/{barisId}/unduh', [CapaianKinerjaController::class, 'unduhBukti'])->name('capaian-kinerja.bukti.unduh');
        Route::post('capaian-kinerja/{iku}/migrasi-triwulan', [CapaianKinerjaController::class, 'migrasiTriwulan'])->name('capaian-kinerja.migrasi-triwulan');

        // Analisa Kinerja
        Route::get('analisa-kinerja', [AnalisaKinerjaController::class, 'index'])->name('analisa-kinerja.index');
        Route::put('analisa-kinerja/{iku}/{triwulan}', [AnalisaKinerjaController::class, 'storeOrUpdate'])->name('analisa-kinerja.store-or-update');
    });
