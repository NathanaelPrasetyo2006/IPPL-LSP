<?php

use App\Http\Controllers\Asesor\BiodataController;
use Illuminate\Support\Facades\Route;

// Menu milik asesor sendiri: biodata dan Kartu Tanda Asesor.
Route::middleware(['auth', 'role:asesor'])
    ->prefix('asesor')
    ->name('asesor.')
    ->group(function () {
        Route::get('biodata', [BiodataController::class, 'edit'])->name('biodata.edit');
        Route::put('biodata', [BiodataController::class, 'update'])->name('biodata.update');
        Route::get('biodata/foto', [BiodataController::class, 'foto'])->name('biodata.foto');

        Route::get('kartu', [BiodataController::class, 'kartu'])->name('kartu');
    });
