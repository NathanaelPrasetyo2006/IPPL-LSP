<?php

use App\Http\Controllers\NotifikasiController;
use App\Http\Controllers\Pendaftaran\PeriodePendaftaranController;
use App\Http\Controllers\Pendaftaran\VerifikasiPermohonanController;
use App\Http\Controllers\Sertifikasi\DokumenPermohonanController;
use App\Http\Controllers\Sertifikasi\PendaftaranSertifikasiController;
use Illuminate\Support\Facades\Route;

// Admin LSP: atur periode pendaftaran dan verifikasi berkas APL-01.
Route::middleware(['auth', 'role:admin'])
    ->prefix('pendaftaran')
    ->name('pendaftaran.')
    ->group(function () {
        Route::patch('periode/{periode}/status', [PeriodePendaftaranController::class, 'toggle'])->name('periode.toggle');
        Route::resource('periode', PeriodePendaftaranController::class)->except('show');

        Route::get('verifikasi', [VerifikasiPermohonanController::class, 'index'])->name('verifikasi.index');
        Route::get('verifikasi/{permohonan}', [VerifikasiPermohonanController::class, 'show'])->name('verifikasi.show');
        Route::put('verifikasi/{permohonan}', [VerifikasiPermohonanController::class, 'update'])->name('verifikasi.update');
    });

// Calon asesi: wajib sudah memverifikasi email sebelum mendaftar sertifikasi.
Route::middleware(['auth', 'role:asesi', 'verified'])
    ->prefix('sertifikasi')
    ->name('sertifikasi.')
    ->group(function () {
        Route::get('/', [PendaftaranSertifikasiController::class, 'index'])->name('index');
        Route::get('daftar/{periode}/{skema}', [PendaftaranSertifikasiController::class, 'create'])->name('create');
        Route::post('daftar/{periode}/{skema}', [PendaftaranSertifikasiController::class, 'store'])->name('store');
        Route::get('permohonan/{permohonan}', [PendaftaranSertifikasiController::class, 'show'])->name('show');
        Route::get('dokumen-saya/{jenis}', [PendaftaranSertifikasiController::class, 'dokumenSaya'])->name('dokumen-saya');
    });

Route::middleware('auth')->group(function () {
    // Hak akses (admin atau pemilik) diperiksa di controller.
    Route::get('dokumen/permohonan/{permohonan}/{jenis}', DokumenPermohonanController::class)->name('dokumen.permohonan');

    Route::post('notifikasi/baca-semua', [NotifikasiController::class, 'bacaSemua'])->name('notifikasi.baca-semua');
    Route::post('notifikasi/{id}/baca', [NotifikasiController::class, 'baca'])->name('notifikasi.baca');
});
