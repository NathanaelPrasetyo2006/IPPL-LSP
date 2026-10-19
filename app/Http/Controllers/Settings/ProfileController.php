<?php

namespace App\Http\Controllers\Settings;

use App\Enums\TujuanOtp;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\KodeVerifikasi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('settings/profile', [
            'emailBaru' => $user->email_baru,
            'otp' => $user->email_baru ? KodeVerifikasi::status($user, TujuanOtp::GantiEmail) : null,
            // Hanya asesi yang mendaftar sendiri; akun admin & asesor dikelola LSP.
            'bisaNonaktif' => $user->role === UserRole::Asesi,
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Minta ganti email. Email lama tetap dipakai sampai kode OTP di email baru dimasukkan.
     *
     * @throws ValidationException
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);
        $data = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id_user, 'id_user')],
        ], [
            'email.unique' => 'Email ini sudah dipakai akun lain.',
        ]);

        if ($data['email'] === $user->email) {
            return to_route('profile.edit')->with('status', 'Email tidak berubah.');
        }

        $user->forceFill(['email_baru' => $data['email']])->save();

        try {
            KodeVerifikasi::kirim($user, TujuanOtp::GantiEmail, $data['email']);
        } catch (ValidationException $e) {
            throw ValidationException::withMessages(['email' => $e->validator->errors()->first('kode')]);
        }

        return to_route('profile.edit')->with('status', "Kode verifikasi dikirim ke {$data['email']}. Email Anda belum berubah sampai kode dimasukkan.");
    }

    /**
     * Masukkan kode dari email baru; baru di sini email benar-benar diganti.
     *
     * @throws ValidationException
     */
    public function verifikasiEmail(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->email_baru) {
            return to_route('profile.edit');
        }

        $request->validate(['kode' => ['required', 'digits:6']], ['kode.digits' => 'Kode terdiri dari 6 digit angka.']);

        KodeVerifikasi::periksa($user, TujuanOtp::GantiEmail, $request->input('kode'));

        // Bisa saja email itu dipakai akun lain selama menunggu kode.
        if (User::query()->where('email', $user->email_baru)->exists()) {
            $user->forceFill(['email_baru' => null])->save();

            throw ValidationException::withMessages(['kode' => 'Email baru sudah dipakai akun lain. Silakan gunakan email lain.']);
        }

        $user->forceFill([
            'email' => $user->email_baru,
            'email_baru' => null,
            'email_verified_at' => now(),
        ])->save();

        return to_route('profile.edit')->with('status', 'Email berhasil diganti.');
    }

    public function kirimUlangEmail(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->email_baru) {
            return to_route('profile.edit');
        }

        KodeVerifikasi::kirim($user, TujuanOtp::GantiEmail, $user->email_baru);

        return back()->with('status', "Kode baru dikirim ke {$user->email_baru}.");
    }

    public function batalEmail(Request $request): RedirectResponse
    {
        $user = $request->user();

        $user->forceFill(['email_baru' => null])->save();
        KodeVerifikasi::batalkan($user, TujuanOtp::GantiEmail);

        return to_route('profile.edit')->with('status', 'Penggantian email dibatalkan.');
    }

    /**
     * Nonaktifkan akun asesi (soft delete). Data tetap tersimpan dan akun bisa diaktifkan kembali lewat OTP.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        // Email wajib diverifikasi ulang saat akun diaktifkan kembali.
        $user->forceFill([
            'nonaktif_pada' => now(),
            'email_verified_at' => null,
            'email_baru' => null,
        ])->save();

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('login.role', 'asesi')
            ->with('status', 'Akun Anda telah dinonaktifkan. Untuk memakainya lagi, masuk atau registrasi dengan email yang sama.');
    }
}
