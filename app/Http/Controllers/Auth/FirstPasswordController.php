<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class FirstPasswordController extends Controller
{
    /**
     * Halaman ganti password untuk pemilik akun yang masih memakai password sementara.
     */
    public function edit(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        // Pengguna yang passwordnya sudah pribadi tidak perlu melihat halaman ini.
        if (! $user->must_change_password) {
            return to_route('dashboard');
        }

        return Inertia::render('auth/first-password', [
            'email' => $user->email,
            'nama' => $user->asesor?->nama,
        ]);
    }

    /**
     * Simpan password baru dan lepas status password sementara.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', Password::defaults(), 'confirmed', 'different:current_password'],
        ], [
            'current_password.current_password' => 'Password sementara yang Anda masukkan tidak cocok.',
            'password.different' => 'Password baru harus berbeda dari password sementara.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ], [
            'current_password' => 'password sementara',
            'password' => 'password baru',
        ]);

        $request->user()->update([
            'password' => $validated['password'],
            'must_change_password' => false,
        ]);

        // ID sesi diputar ulang setelah kredensial berubah (proteksi session fixation).
        $request->session()->regenerate();

        return to_route('dashboard')->with('success', 'Password berhasil diganti. Selamat datang di Sistem Informasi LSP.');
    }
}
