<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\DataDiri;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Halaman registrasi akun calon asesi.
     */
    public function create(): Response
    {
        return Inertia::render('auth/register');
    }

    /**
     * Buat akun calon asesi beserta profil awalnya, lalu kirim kode verifikasi ke email.
     * Akun baru bisa dipakai setelah kodenya dimasukkan; data diri lengkap (APL-01) diisi sesudahnya.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'nama_lengkap' => is_string($request->nama_lengkap) ? trim($request->nama_lengkap) : $request->nama_lengkap,
            'no_telp' => is_string($request->no_telp) ? preg_replace('/[\s\-()]/', '', $request->no_telp) : $request->no_telp,
        ]);

        $validated = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'no_telp' => ['required', 'string', 'regex:/^\+?[0-9]{9,15}$/'],
            // Email milik akun nonaktif boleh dipakai: diarahkan ke aktivasi ulang di bawah.
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->whereNull('nonaktif_pada')],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            'persetujuan' => ['accepted'],
        ], [
            'email.unique' => 'Email ini sudah terdaftar. Silakan masuk atau gunakan fitur lupa password.',
            'email.lowercase' => 'Email harus ditulis dengan huruf kecil.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'no_telp.regex' => 'No. HP hanya boleh berisi angka (boleh diawali +), 9–15 digit.',
            'persetujuan.accepted' => 'Anda harus menyetujui penggunaan data untuk proses sertifikasi.',
        ], [
            'nama_lengkap' => 'nama lengkap',
            'no_telp' => 'no. HP',
            'password' => 'password',
        ]);

        // Akun nonaktif selalu milik asesi, karena admin & asesor tidak bisa menonaktifkan akunnya.
        $nonaktif = User::query()->where('email', $validated['email'])->whereNotNull('nonaktif_pada')->first();

        if ($nonaktif) {
            $request->session()->put(AktivasiAkunController::SESSION, [
                'id_user' => $nonaktif->id_user,
                'asal' => 'registrasi',
                'password_baru' => Hash::make($validated['password']),
            ]);

            return to_route('aktivasi.show');
        }

        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'email' => $validated['email'],
                'password' => $validated['password'],
                'role' => UserRole::Asesi,
            ]);

            // Kode asesi dibuat otomatis oleh model saat data diri tersimpan.
            DataDiri::create([
                'id_user' => $user->id_user,
                'nama_lengkap' => $validated['nama_lengkap'],
                'no_telp' => $validated['no_telp'],
            ]);

            return $user;
        });

        // Listener bawaan Laravel mengirim kode verifikasi (User::sendEmailVerificationNotification) untuk event ini.
        event(new Registered($user));

        // Belum login: akun masuk ke sistem setelah kode verifikasi benar.
        VerifikasiEmailController::mulai($request, $user);

        return to_route('verifikasi-email.show');
    }
}
