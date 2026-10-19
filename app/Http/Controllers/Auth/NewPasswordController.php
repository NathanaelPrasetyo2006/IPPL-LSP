<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Lupa password langkah 3: buat password baru setelah kode OTP benar.
 */
class NewPasswordController extends Controller
{
    public function create(Request $request, UserRole $role): Response|RedirectResponse
    {
        $user = $this->pemohonTerverifikasi($request, $role);

        if (! $user) {
            return to_route('password.request', $role);
        }

        return Inertia::render('auth/reset-password', [
            'role' => $role->value,
            'email' => $user->email,
        ]);
    }

    public function store(Request $request, UserRole $role): RedirectResponse
    {
        $user = $this->pemohonTerverifikasi($request, $role);

        if (! $user) {
            return to_route('password.request', $role);
        }

        $request->validate([
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        $user->forceFill([
            'password' => $request->input('password'),
            'remember_token' => Str::random(60),
            // Password hasil reset dibuat sendiri oleh pemilik akun, bukan password sementara.
            'must_change_password' => false,
            // Kode OTP dari email sudah membuktikan kepemilikan email tersebut.
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        event(new PasswordReset($user));

        $request->session()->forget(PasswordResetLinkController::SESSION);

        return to_route('login.role', $role)
            ->with('status', 'Password berhasil direset. Silakan masuk dengan password baru Anda.');
    }

    private function pemohonTerverifikasi(Request $request, UserRole $role): ?User
    {
        $sesi = $request->session()->get(PasswordResetLinkController::SESSION);

        if (($sesi['role'] ?? null) !== $role->value || ! ($sesi['terverifikasi'] ?? false)) {
            return null;
        }

        return User::query()->whereKey($sesi['id_user'])->whereNull('nonaktif_pada')->first();
    }
}
