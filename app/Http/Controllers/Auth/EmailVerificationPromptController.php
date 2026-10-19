<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmailVerificationPromptController extends Controller
{
    /**
     * Tujuan middleware `verified`. Akun yang masih login tetapi belum terverifikasi
     * (mis. sesi lama sebelum verifikasi OTP berlaku) dikeluarkan lalu diarahkan ke halaman kode.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        VerifikasiEmailController::mulai($request, $user);

        return to_route('verifikasi-email.show');
    }
}
