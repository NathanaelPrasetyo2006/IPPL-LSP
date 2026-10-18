<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Notifikasi di aplikasi (ikon lonceng di header).
 */
class NotifikasiController extends Controller
{
    /**
     * Tandai dibaca lalu buka halaman yang dirujuk notifikasi.
     */
    public function baca(Request $request, string $id): RedirectResponse
    {
        $notifikasi = $request->user()->notifications()->findOrFail($id);
        $notifikasi->markAsRead();

        $url = $notifikasi->data['url'] ?? null;

        // Hanya path internal yang diikuti supaya tidak bisa dipakai sebagai open redirect.
        return is_string($url) && str_starts_with($url, '/') && ! str_starts_with($url, '//')
            ? redirect($url)
            : back();
    }

    public function bacaSemua(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back();
    }
}
