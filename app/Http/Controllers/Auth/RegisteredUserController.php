<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Halaman registrasi akun asesi.
     */
    public function create(): Response
    {
        return Inertia::render('auth/register');
    }

    /**
     * Buat akun asesi baru. Data diri (APL-01) dilengkapi setelah login.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
        ], [
            'email.unique' => 'Email ini sudah terdaftar. Silakan masuk atau gunakan fitur lupa password.',
            'email.lowercase' => 'Email harus ditulis dengan huruf kecil.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ], [
            'password' => 'password',
        ]);

        $user = User::create([
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => UserRole::Asesi,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return to_route('dashboard')->with('success', 'Akun asesi berhasil dibuat. Selamat datang di Sistem Informasi LSP.');
    }
}
