<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAkunAktif
{
    /**
     * Keluarkan sesi akun yang sudah dinonaktifkan, mis. masih login di perangkat lain.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isNonaktif()) {
            $role = $request->user()->role;

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return to_route('login.role', $role)->with('status', 'Akun Anda nonaktif. Masuk kembali untuk mengaktifkannya.');
        }

        return $next($request);
    }
}
