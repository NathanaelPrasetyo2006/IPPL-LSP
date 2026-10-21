<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

/**
 * Email tunggal yang dikirim saat admin membuat akun asesor: berisi email login,
 * password sementara, dan link verifikasi. Akun baru bisa dipakai login setelah link diklik.
 */
class AsesorVerifikasiAkun extends Notification
{
    /**
     * Masa berlaku link verifikasi, dalam jam.
     */
    public const BERLAKU_JAM = 24;

    public function __construct(
        private readonly string $nama,
        #[\SensitiveParameter] private readonly string $password,
    ) {}

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
            ->subject('Akun Asesor LSP - Verifikasi & Password Sementara')
            ->greeting("Halo, {$this->nama}")
            ->line('Admin LSP telah membuatkan akun asesor untuk Anda. Berikut data untuk login pertama kali:')
            ->line("**Email:** {$notifiable->email}")
            ->line("**Password sementara:** `{$this->password}`")
            ->line('Sebelum bisa login, klik tombol di bawah untuk memverifikasi email dan mengaktifkan akun.')
            ->action('Verifikasi & Aktifkan Akun', self::url($notifiable))
            ->line('Demi keamanan, sistem akan meminta Anda mengganti password sementara ini segera setelah login.')
            ->line('Link ini berlaku '.self::BERLAKU_JAM.' jam. Abaikan email ini bila Anda tidak merasa didaftarkan sebagai asesor.')
            ->salutation('Salam, Admin LSP');
    }

    /**
     * Link bertanda tangan; hash email membuat link lama gugur bila email akun diubah.
     */
    public static function url(User $user): string
    {
        return URL::temporarySignedRoute('asesor.verifikasi', now()->addHours(self::BERLAKU_JAM), [
            'user' => $user,
            'hash' => sha1($user->email),
        ]);
    }
}
