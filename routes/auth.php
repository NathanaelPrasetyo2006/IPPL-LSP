<?php

use App\Http\Controllers\Auth\AktivasiAkunController;
use App\Http\Controllers\Auth\AsesorVerificationController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\FirstPasswordController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\PasswordResetOtpController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

// Link aktivasi akun asesor dari email. Tidak dibatasi guest karena bisa saja dibuka
// di browser yang sedang login sebagai admin; keabsahan dijamin tanda tangan URL.
Route::get('asesor/verifikasi/{user}/{hash}', AsesorVerificationController::class)
    ->middleware('throttle:6,1')
    ->name('asesor.verifikasi');

Route::middleware('guest')->group(function () {
    // Registrasi mandiri hanya untuk asesi; akun admin & asesor dibuat oleh admin LSP.
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store']);

    // Portal pemilihan peran, lalu formulir login per peran (admin, asesor, asesi).
    Route::get('login', [AuthenticatedSessionController::class, 'portal'])
        ->name('login');

    Route::get('login/{role}', [AuthenticatedSessionController::class, 'create'])
        ->name('login.role');

    Route::post('login/{role}', [AuthenticatedSessionController::class, 'store'])
        ->name('login.store');

    // Lupa password: email → kode OTP → password baru. Kemajuan tiap langkah disimpan di session.
    Route::get('forgot-password/{role}', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password/{role}', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get('forgot-password/{role}/kode', [PasswordResetOtpController::class, 'show'])
        ->name('password.otp');

    Route::post('forgot-password/{role}/kode', [PasswordResetOtpController::class, 'verify'])
        ->name('password.otp.verify');

    Route::post('forgot-password/{role}/kirim-ulang', [PasswordResetOtpController::class, 'resend'])
        ->name('password.otp.resend');

    Route::get('forgot-password/{role}/password-baru', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('forgot-password/{role}/password-baru', [NewPasswordController::class, 'store'])
        ->name('password.store');

    // Aktivasi ulang akun asesi nonaktif, dimulai dari login atau registrasi.
    Route::get('aktivasi-akun', [AktivasiAkunController::class, 'show'])
        ->name('aktivasi.show');

    Route::post('aktivasi-akun/kirim', [AktivasiAkunController::class, 'kirim'])
        ->name('aktivasi.kirim');

    Route::post('aktivasi-akun', [AktivasiAkunController::class, 'verifikasi'])
        ->name('aktivasi.verifikasi');
});

Route::middleware('auth')->group(function () {
    Route::get('first-password', [FirstPasswordController::class, 'edit'])
        ->name('password.first-change');

    Route::post('first-password', [FirstPasswordController::class, 'update'])
        ->name('password.first-change.update');

    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
