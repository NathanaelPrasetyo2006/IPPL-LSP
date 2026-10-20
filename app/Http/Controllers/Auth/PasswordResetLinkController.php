<?php

namespace App\Http\Controllers\Auth;

use App\Enums\TujuanOtp;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\KodeVerifikasi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Lupa password langkah 1: masukkan email, lalu kode OTP dikirim ke email tersebut.
 */
class PasswordResetLinkController extends Controller
{
    /**
     * Key session yang menyimpan proses lupa password yang sedang berjalan.
     */
    public const SESSION = 'reset_password';

    /**
     * Membuka langkah 1 selalu memulai proses baru: email dikosongkan dan proses sebelumnya
     * (termasuk milik peran lain) dibuang, supaya email lama tidak terbawa.
     */
    public function create(Request $request, UserRole $role): Response
    {
        $request->session()->forget(self::SESSION);

        return Inertia::render('auth/forgot-password', [
            'role' => $role->value,
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function store(Request $request, UserRole $role): RedirectResponse
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);
        $request->validate(['email' => ['required', 'email']]);

        $user = User::query()->where('email', $request->input('email'))->where('role', $role)->first();

        if (! $user) {
            throw ValidationException::withMessages(['email' => "Email tidak terdaftar sebagai {$role->label()}."]);
        }

        if ($user->isNonaktif()) {
            throw ValidationException::withMessages([
                'email' => 'Akun ini nonaktif. Aktifkan kembali lewat halaman masuk atau registrasi dengan email yang sama.',
            ]);
        }

        // Pengguna yang kembali ke langkah 1 masih terkena jeda kirim ulang; kode lama tetap bisa dipakai.
        try {
            KodeVerifikasi::kirim($user, TujuanOtp::ResetPassword);
        } catch (ValidationException $e) {
            if (! KodeVerifikasi::status($user, TujuanOtp::ResetPassword)['terkirim']) {
                throw ValidationException::withMessages(['email' => $e->validator->errors()->first('kode')]);
            }
        }

        $request->session()->put(self::SESSION, [
            'id_user' => $user->id_user,
            'email' => $user->email,
            'role' => $role->value,
            'terverifikasi' => false,
        ]);

        return to_route('password.otp', $role);
    }
}
