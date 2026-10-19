<?php

use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Ganti email: email lama tetap dipakai sampai kode OTP di email baru dimasukkan.
    Route::post('settings/profile/email/verifikasi', [ProfileController::class, 'verifikasiEmail'])->name('profile.email.verify');
    Route::post('settings/profile/email/kirim-ulang', [ProfileController::class, 'kirimUlangEmail'])->name('profile.email.resend');
    Route::delete('settings/profile/email', [ProfileController::class, 'batalEmail'])->name('profile.email.cancel');

    // Hanya asesi yang bisa menonaktifkan akunnya sendiri.
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->middleware('role:asesi')->name('profile.destroy');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('settings/password', [PasswordController::class, 'update'])->name('password.update');

    Route::get('settings/appearance', function () {
        return Inertia::render('settings/appearance');
    })->name('appearance');
});
