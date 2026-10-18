<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Email reset password berbahasa Indonesia. Tombolnya membuka halaman
 * pembuatan password baru di aplikasi.
 */
class ResetPasswordNotification extends ResetPassword
{
    protected function buildMailMessage($url): MailMessage
    {
        $minutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject('Reset Password Akun LSP')
            ->greeting('Halo!')
            ->line('Kami menerima permintaan untuk mengatur ulang password akun Anda.')
            ->action('Buat Password Baru', $url)
            ->line("Link ini berlaku {$minutes} menit.")
            ->line('Bila Anda tidak meminta reset password, abaikan email ini. Password Anda tidak akan berubah.')
            ->salutation('Salam, Admin LSP');
    }
}
