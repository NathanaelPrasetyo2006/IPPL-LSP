<?php

use App\Http\Controllers\Master\AsesorController;
use App\Http\Controllers\Master\SkemaSertifikasiController;
use App\Http\Controllers\Master\TukController;
use App\Http\Controllers\Master\UnitKompetensiController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')
    ->prefix('master')
    ->name('master.')
    ->group(function () {
        // Asesor boleh melihat skema & TUK (baca saja) sebagai acuan saat bertugas.
        Route::middleware('role:admin,asesor')->group(function () {
            Route::get('skema-sertifikasi', [SkemaSertifikasiController::class, 'index'])->name('skema-sertifikasi.index');
            Route::get('tuk', [TukController::class, 'index'])->name('tuk.index');
        });

        // Perubahan data master hanya oleh admin LSP.
        Route::middleware('role:admin')->group(function () {
            Route::resource('skema-sertifikasi', SkemaSertifikasiController::class)
                ->parameters(['skema-sertifikasi' => 'skema'])
                ->except(['index', 'show']);

            // Hapus unit dilakukan lewat susun() bersama perubahan urutan, saat admin menekan Simpan.
            Route::put('skema-sertifikasi/{skema}/unit-kompetensi/susun', [UnitKompetensiController::class, 'susun'])
                ->name('skema-sertifikasi.unit-kompetensi.susun');

            Route::resource('skema-sertifikasi.unit-kompetensi', UnitKompetensiController::class)
                ->parameters(['skema-sertifikasi' => 'skema', 'unit-kompetensi' => 'unit'])
                ->only(['store', 'update'])
                ->shallow();

            Route::resource('tuk', TukController::class)->except(['index', 'show']);

            Route::post('asesor/{asesor}/kirim-ulang-verifikasi', [AsesorController::class, 'resendVerification'])
                ->middleware('throttle:6,1')
                ->name('asesor.resend-verification');

            Route::resource('asesor', AsesorController::class)->except('show');
        });
    });
