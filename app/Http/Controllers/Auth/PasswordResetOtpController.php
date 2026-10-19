<?php

namespace App\Http\Controllers\Auth;

use App\Enums\TujuanOtp;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\KodeVerifikasi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Lupa password langkah 2: masukkan kode OTP dari email.
 */
class PasswordResetOtpController extends Controller
{
    public function show(Request $request, UserRole $role): Response|RedirectResponse
    {
        $user = $this->pemohon($request, $role);

        if (! $user) {
            return to_route('password.request', $role);
        }

        return Inertia::render('auth/forgot-password-kode', [
            'role' => $role->value,
            'email' => KodeVerifikasi::samarkan($user->email),
            'otp' => KodeVerifikasi::status($user, TujuanOtp::ResetPassword),
        ]);
    }

    public function verify(Request $request, UserRole $role): RedirectResponse
    {
        $user = $this->pemohon($request, $role);

        if (! $user) {
            return to_route('password.request', $role);
        }

        $request->validate(['kode' => ['required', 'digits:6']], ['kode.digits' => 'Kode terdiri dari 6 digit angka.']);

        KodeVerifikasi::periksa($user, TujuanOtp::ResetPassword, $request->input('kode'));

        $request->session()->put(PasswordResetLinkController::SESSION.'.terverifikasi', true);

        return to_route('password.reset', $role);
    }

    public function resend(Request $request, UserRole $role): RedirectResponse
    {
        $user = $this->pemohon($request, $role);

        if (! $user) {
            return to_route('password.request', $role);
        }

        KodeVerifikasi::kirim($user, TujuanOtp::ResetPassword);

        return back()->with('status', 'Kode baru sudah dikirim ke email Anda.');
    }

    /**
     * Akun yang sedang menjalani lupa password lewat portal peran ini, atau null bila sesi tidak ada.
     */
    private function pemohon(Request $request, UserRole $role): ?User
    {
        $sesi = $request->session()->get(PasswordResetLinkController::SESSION);

        if (($sesi['role'] ?? null) !== $role->value) {
            return null;
        }

        return User::query()->whereKey($sesi['id_user'])->whereNull('nonaktif_pada')->first();
    }
}
