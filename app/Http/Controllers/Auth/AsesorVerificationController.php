<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\AsesorPasswordSementara;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AsesorVerificationController extends Controller
{
    /**
     * Aktifkan akun asesor dari link verifikasi di email, lalu kirim password sementaranya.
     */
    public function __invoke(Request $request, User $user, string $hash): Response
    {
        // Tanda tangan dicek manual agar link kedaluwarsa tetap mendapat halaman yang jelas.
        $valid = $request->hasValidSignature()
            && $user->role === UserRole::Asesor
            && hash_equals(sha1($user->email), $hash);

        if (! $valid) {
            return $this->render('invalid', $user);
        }

        // Link yang diklik ulang tidak boleh menimpa password yang sudah dikirim.
        if ($user->isVerified()) {
            return $this->render('already', $user);
        }

        $password = Str::password(12, symbols: false);

        $user->forceFill([
            'password' => $password,
            'must_change_password' => true,
            'email_verified_at' => now(),
        ])->save();

        $user->notify(new AsesorPasswordSementara($user->asesor?->nama ?? $user->email, $password));

        return $this->render('activated', $user);
    }

    private function render(string $status, User $user): Response
    {
        return Inertia::render('auth/asesor-verification', [
            'status' => $status,
            'email' => $status === 'invalid' ? null : $user->email,
        ]);
    }
}
