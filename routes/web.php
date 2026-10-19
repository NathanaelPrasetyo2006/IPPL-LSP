<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Tidak ada halaman landing: tamu langsung ke portal login, pengguna yang sudah masuk ke dashboard.
Route::get('/', function () {
    return redirect()->route(Auth::check() ? 'dashboard' : 'login');
})->name('home');

// Akun yang emailnya belum terverifikasi (mis. registrasi dengan email asal-asalan) tidak bisa masuk beranda.
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

require __DIR__.'/master.php';
require __DIR__.'/asesor.php';
require __DIR__.'/pendaftaran.php';
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
