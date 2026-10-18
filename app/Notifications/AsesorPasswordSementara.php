<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Password sementara yang dikirim setelah asesor memverifikasi akunnya.
 */
class AsesorPasswordSementara extends Notification
{
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

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Akun Asesor Aktif - Password Sementara')
            ->greeting("Halo, {$this->nama}")
            ->line('Akun asesor Anda sudah aktif. Gunakan data berikut untuk login pertama kali:')
            ->line("**Email:** {$notifiable->email}")
            ->line("**Password sementara:** `{$this->password}`")
            ->action('Masuk sebagai Asesor', route('login.role', 'asesor'))
            ->line('Demi keamanan, sistem akan meminta Anda mengganti password sementara ini segera setelah login.')
            ->salutation('Salam, Admin LSP');
    }
}
