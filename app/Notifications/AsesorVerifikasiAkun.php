<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

/**
 * Link verifikasi yang dikirim saat admin membuat akun asesor.
 * Setelah diklik, akun aktif dan password sementara dikirim lewat email terpisah.
 */
class AsesorVerifikasiAkun extends Notification
{
    /**
     * Masa berlaku link verifikasi, dalam jam.
     */
    public const BERLAKU_JAM = 24;

    public function __construct(private readonly string $nama) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Verifikasi Akun Asesor LSP')
            ->greeting("Halo, {$this->nama}")
            ->line('Admin LSP telah membuatkan akun asesor untuk Anda. Klik tombol di bawah untuk memverifikasi email dan mengaktifkan akun.')
            ->action('Verifikasi & Aktifkan Akun', self::url($notifiable))
            ->line('Setelah akun aktif, password sementara akan dikirim ke email ini. Anda wajib menggantinya saat login pertama.')
            ->line('Link ini berlaku '.self::BERLAKU_JAM.' jam. Abaikan email ini bila Anda tidak merasa didaftarkan sebagai asesor.')
            ->salutation('Salam, Admin LSP');
    }

    /**
     * Link bertanda tangan; hash email membuat link lama gugur bila email akun diubah.
     */
    public static function url(User $user): string
    {
        return URL::temporarySignedRoute('asesor.verifikasi', now()->addHours(self::BERLAKU_JAM), [
            'user' => $user->id_user,
            'hash' => sha1($user->email),
        ]);
    }
}
