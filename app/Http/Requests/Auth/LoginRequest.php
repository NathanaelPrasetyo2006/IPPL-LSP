<?php

namespace App\Http\Requests\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials for the given portal role.
     *
     * @throws ValidationException
     */
    public function authenticate(UserRole $role): void
    {
        $this->ensureIsNotRateLimited();

        // Peran ikut dicocokkan, jadi akun asesi tidak bisa masuk lewat portal admin, dst.
        // Akun nonaktif ditangani terpisah lewat akunNonaktif().
        $credentials = [
            ...$this->only('email', 'password'),
            'role' => $role->value,
            fn ($query) => $query->whereNull('nonaktif_pada'),
        ];

        // Akun asesor dan asesi baru bisa dipakai setelah email-nya diverifikasi.
        // Asesi yang belum verifikasi ditangani terpisah lewat akunBelumVerifikasi().
        if ($role !== UserRole::Admin) {
            $credentials[] = fn ($query) => $query->whereNotNull('email_verified_at');
        }

        if (! Auth::attempt($credentials, $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => 'Email atau password salah, atau akun ini tidak terdaftar sebagai '.$role->label().'.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Akun nonaktif dengan email & password yang benar, untuk diarahkan ke aktivasi ulang.
     * Password salah tetap diperlakukan seperti login gagal biasa.
     *
     * @throws ValidationException
     */
    public function akunNonaktif(UserRole $role): ?User
    {
        $this->ensureIsNotRateLimited();

        $user = User::query()
            ->where('email', $this->string('email')->lower()->toString())
            ->where('role', $role)
            ->whereNotNull('nonaktif_pada')
            ->first();

        return $user && Hash::check($this->string('password')->toString(), $user->password) ? $user : null;
    }

    /**
     * Akun asesi dengan email & password benar yang emailnya belum diverifikasi,
     * untuk diarahkan ke halaman kode verifikasi.
     *
     * @throws ValidationException
     */
    public function akunBelumVerifikasi(UserRole $role): ?User
    {
        if ($role !== UserRole::Asesi) {
            return null;
        }

        $this->ensureIsNotRateLimited();

        $user = User::query()
            ->where('email', $this->string('email')->lower()->toString())
            ->where('role', $role)
            ->whereNull('email_verified_at')
            ->whereNull('nonaktif_pada')
            ->first();

        return $user && Hash::check($this->string('password')->toString(), $user->password) ? $user : null;
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => "Terlalu banyak percobaan masuk. Coba lagi dalam {$seconds} detik.",
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
