<?php

namespace App\Http\Controllers\Auth;

use App\Enums\TujuanOtp;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\KodeVerifikasi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Aktivasi ulang akun asesi yang pernah dinonaktifkan, dimulai dari login atau registrasi,
 * lalu dibuktikan dengan kode OTP ke email akun.
 */
class AktivasiAkunController extends Controller
{
    /**
     * Isi session: id_user, asal (login|registrasi), dan password_baru (hash, hanya dari registrasi).
     */
    public const SESSION = 'aktivasi_akun';

    public function show(Request $request): Response|RedirectResponse
    {
        $user = $this->akun($request);

        if (! $user) {
            return to_route('login.role', 'asesi');
        }

        return Inertia::render('auth/aktivasi-akun', [
            'email' => KodeVerifikasi::samarkan($user->email),
            'asal' => $request->session()->get(self::SESSION.'.asal'),
            'nonaktifSejak' => $user->nonaktif_pada->locale('id')->translatedFormat('d F Y'),
            'otp' => KodeVerifikasi::status($user, TujuanOtp::AktivasiAkun),
        ]);
    }

    /**
     * Kirim (atau kirim ulang) kode aktivasi ke email akun.
     */
    public function kirim(Request $request): RedirectResponse
    {
        $user = $this->akun($request);

        if (! $user) {
            return to_route('login.role', 'asesi');
        }

        KodeVerifikasi::kirim($user, TujuanOtp::AktivasiAkun);

        return back()->with('status', 'Kode verifikasi sudah dikirim ke email Anda.');
    }

    public function verifikasi(Request $request): RedirectResponse
    {
        $user = $this->akun($request);

        if (! $user) {
            return to_route('login.role', 'asesi');
        }

        $request->validate(['kode' => ['required', 'digits:6']], ['kode.digits' => 'Kode terdiri dari 6 digit angka.']);

        KodeVerifikasi::periksa($user, TujuanOtp::AktivasiAkun, $request->input('kode'));

        $passwordBaru = $request->session()->get(self::SESSION.'.password_baru');

        $user->forceFill([
            'nonaktif_pada' => null,
            'email_verified_at' => now(),
            // Dari registrasi, password diganti dengan yang baru diisi; dari login, password lama tetap.
            ...($passwordBaru ? ['password' => $passwordBaru] : []),
        ])->save();

        $request->session()->forget(self::SESSION);

        Auth::login($user);
        $request->session()->regenerate();

        return to_route('dashboard')->with('success', 'Akun Anda aktif kembali. Data dan riwayat permohonan Anda tetap tersimpan.');
    }

    private function akun(Request $request): ?User
    {
        $id = $request->session()->get(self::SESSION.'.id_user');

        return $id ? User::query()->whereKey($id)->whereNotNull('nonaktif_pada')->first() : null;
    }
}
