<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsChanged
{
    /**
     * Route yang tetap boleh diakses selama password sementara belum diganti.
     *
     * @var list<string>
     */
    private const ALLOWED_ROUTES = [
        'password.first-change',
        'password.first-change.update',
        'logout',
    ];

    /**
     * Kunci pengguna yang masih memakai password sementara ke halaman ganti password.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->must_change_password && ! $request->routeIs(...self::ALLOWED_ROUTES)) {
            return redirect()->route('password.first-change');
        }

        return $next($request);
    }
}
