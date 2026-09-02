<?php

use App\Http\Controllers\Master\SkemaSertifikasiController;
use App\Http\Controllers\Master\TukController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')
    ->prefix('master')
    ->name('master.')
    ->group(function () {
        Route::resource('skema-sertifikasi', SkemaSertifikasiController::class)
            ->parameters(['skema-sertifikasi' => 'skema'])
            ->except('show');

        Route::resource('tuk', TukController::class)->except('show');
    });
