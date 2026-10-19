<?php

namespace App\Http\Controllers\Auth;

use App\Enums\TujuanOtp;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\KodeVerifikasi;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Verifikasi email calon asesi dengan kode OTP, setelah registrasi atau saat login sebelum terverifikasi.
 * Akun baru masuk ke sistem setelah kodenya benar.
 */
class VerifikasiEmailController extends Controller
{
    /**
     * Isi session: id_user akun yang sedang diverifikasi.
     */
    public const SESSION = 'verifikasi_email';

    /**
     * Mulai verifikasi untuk akun ini: simpan di session lalu kirim kode.
     * Bila masih dalam jeda kirim ulang, kode sebelumnya tetap bisa dipakai.
     */
    public static function mulai(Request $request, User $user): void
    {
        $request->session()->put(self::SESSION, ['id_user' => $user->id_user]);

        try {
            KodeVerifikasi::kirim($user, TujuanOtp::VerifikasiEmail);
        } catch (ValidationException) {
            // Jeda kirim ulang atau batas harian; halaman kode menampilkan sisa waktunya.
        }
    }

    public function show(Request $request): Response|RedirectResponse
    {
        $user = $this->akun($request);

        if (! $user) {
            return to_route('login.role', 'asesi');
        }

        return Inertia::render('auth/verifikasi-email', [
            'email' => KodeVerifikasi::samarkan($user->email),
            'otp' => KodeVerifikasi::status($user, TujuanOtp::VerifikasiEmail),
        ]);
    }

    public function verifikasi(Request $request): RedirectResponse
    {
        $user = $this->akun($request);

        if (! $user) {
            return to_route('login.role', 'asesi');
        }

        $request->validate(['kode' => ['required', 'digits:6']], ['kode.digits' => 'Kode terdiri dari 6 digit angka.']);

        KodeVerifikasi::periksa($user, TujuanOtp::VerifikasiEmail, $request->input('kode'));

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        $request->session()->forget(self::SESSION);

        Auth::login($user);
        $request->session()->regenerate();

        return to_route('dashboard')->with('success', 'Email berhasil diverifikasi. Selamat datang!');
    }

    public function kirimUlang(Request $request): RedirectResponse
    {
        $user = $this->akun($request);

        if (! $user) {
            return to_route('login.role', 'asesi');
        }

        KodeVerifikasi::kirim($user, TujuanOtp::VerifikasiEmail);

        return back()->with('status', 'Kode baru sudah dikirim ke email Anda.');
    }

    /**
     * Akun asesi aktif yang emailnya belum terverifikasi, sesuai session.
     */
    private function akun(Request $request): ?User
    {
        $id = $request->session()->get(self::SESSION.'.id_user');

        return $id
            ? User::query()->whereKey($id)->whereNull('email_verified_at')->whereNull('nonaktif_pada')->first()
            : null;
    }
}
