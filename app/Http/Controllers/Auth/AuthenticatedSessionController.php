<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class AuthenticatedSessionController extends Controller
{
    /**
     * Portal pemilihan peran sebelum masuk.
     */
    public function portal(): Response
    {
        return Inertia::render('auth/login-portal');
    }

    /**
     * Formulir login untuk satu peran.
     */
    public function create(Request $request, UserRole $role): Response
    {
        return Inertia::render('auth/login', [
            'role' => $role->value,
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request, UserRole $role): RedirectResponse
    {
        if ($nonaktif = $request->akunNonaktif($role)) {
            $request->session()->put(AktivasiAkunController::SESSION, [
                'id_user' => $nonaktif->id_user,
                'asal' => 'login',
                'password_baru' => null,
            ]);

            return to_route('aktivasi.show');
        }

        if ($belumVerifikasi = $request->akunBelumVerifikasi($role)) {
            VerifikasiEmailController::mulai($request, $belumVerifikasi);

            return to_route('verifikasi-email.show')->with('status', 'Email Anda belum diverifikasi. Kode verifikasi sudah dikirim ke email Anda.');
        }

        $request->authenticate($role);

        $request->session()->regenerate();

        return redirect($this->tujuanSetelahMasuk($request));
    }

    /**
     * Halaman yang tadinya ingin dibuka sebelum diminta login, selama boleh dibuka peran akun ini.
     * URL sisa sesi peran lain (mis. menu asesi yang kedaluwarsa di browser yang sama) diabaikan
     * supaya tidak berujung 403.
     */
    private function tujuanSetelahMasuk(Request $request): string
    {
        $dashboard = route('dashboard', absolute: false);
        $tujuan = $request->session()->pull('url.intended');

        if (! is_string($tujuan)) {
            return $dashboard;
        }

        try {
            $route = Route::getRoutes()->match(Request::create($tujuan));
        } catch (Throwable) {
            return $dashboard;
        }

        $peran = $request->user()->role->value;

        foreach ($route->gatherMiddleware() as $middleware) {
            if (is_string($middleware) && str_starts_with($middleware, 'role:') && ! in_array($peran, explode(',', substr($middleware, 5)), true)) {
                return $dashboard;
            }
        }

        return $tujuan;
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
