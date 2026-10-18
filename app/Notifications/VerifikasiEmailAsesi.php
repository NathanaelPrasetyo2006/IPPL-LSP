<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Email verifikasi berbahasa Indonesia untuk akun yang mendaftar mandiri (calon asesi).
 */
class VerifikasiEmailAsesi extends VerifyEmail
{
    protected function buildMailMessage($url): MailMessage
    {
        $menit = config('auth.verification.expire', 60);

        return (new MailMessage)
            ->subject('Verifikasi Email Akun Asesi')
            ->greeting('Halo!')
            ->line('Terima kasih telah mendaftar sebagai calon asesi. Klik tombol di bawah untuk memverifikasi alamat email Anda.')
            ->action('Verifikasi Email', $url)
            ->line("Link ini berlaku {$menit} menit. Setelah terverifikasi, Anda dapat mengisi formulir permohonan sertifikasi (APL-01).")
            ->line('Bila Anda tidak merasa mendaftar, abaikan email ini.')
            ->salutation('Salam, Admin LSP');
    }
}
