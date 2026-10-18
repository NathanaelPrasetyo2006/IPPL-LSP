<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class NewPasswordController extends Controller
{
    /**
     * Show the password reset page.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/reset-password', [
            'email' => $request->email,
            'token' => $request->route('token'),
        ]);
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        $resetUser = null;

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user) use ($request, &$resetUser) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                    // Password hasil reset dibuat sendiri oleh pemilik akun, bukan password sementara.
                    'must_change_password' => false,
                    // Membuka link dari email sudah membuktikan kepemilikan email tersebut.
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ])->save();

                event(new PasswordReset($user));

                $resetUser = $user;
            }
        );

        // Setelah berhasil, arahkan langsung ke portal login sesuai peran akun.
        if ($status == Password::PasswordReset) {
            return to_route('login.role', $resetUser->role)
                ->with('status', 'Password berhasil direset. Silakan masuk dengan password baru Anda.');
        }

        throw ValidationException::withMessages([
            'email' => [match ($status) {
                Password::InvalidToken => 'Tautan reset password tidak valid atau sudah kedaluwarsa. Silakan minta tautan baru.',
                Password::InvalidUser => 'Email tidak cocok dengan tautan reset password ini.',
                default => __($status),
            }],
        ]);
    }
}
