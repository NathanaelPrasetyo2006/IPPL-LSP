<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetLinkController extends Controller
{
    /**
     * Halaman permintaan tautan reset password untuk satu peran.
     */
    public function create(Request $request, UserRole $role): Response
    {
        return Inertia::render('auth/forgot-password', [
            'role' => $role->value,
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Kirim tautan reset password ke akun dengan email dan peran yang cocok.
     *
     * @throws ValidationException
     */
    public function store(Request $request, UserRole $role): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        Password::sendResetLink([
            'email' => $request->input('email'),
            'role' => $role->value,
        ]);

        // Pesan selalu sama agar keberadaan suatu email tidak bisa ditebak.
        return back()->with('status', 'Jika email tersebut terdaftar sebagai '.$role->label().', tautan reset password sudah kami kirim. Periksa kotak masuk atau folder spam Anda.');
    }
}
