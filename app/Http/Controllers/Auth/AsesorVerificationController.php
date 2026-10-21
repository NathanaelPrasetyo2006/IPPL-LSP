<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AsesorVerificationController extends Controller
{
    /**
     * Aktifkan akun asesor dari link verifikasi di email. Password sementara sudah
     * dikirim di email yang sama, jadi asesor bisa langsung login setelahnya.
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

        if ($user->isVerified()) {
            return $this->render('already', $user);
        }

        $user->forceFill(['email_verified_at' => now()])->save();

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
