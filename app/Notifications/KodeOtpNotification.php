<?php

namespace App\Notifications;

use App\Enums\TujuanOtp;
use App\Support\KodeVerifikasi;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email berisi kode OTP 6 digit.
 */
class KodeOtpNotification extends Notification
{
    public function __construct(
        public readonly string $kode,
        public readonly TujuanOtp $tujuan,
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
            ->subject("Kode {$this->tujuan->label()} Akun LSP")
            ->greeting('Halo!')
            ->line($this->tujuan->keterangan())
            ->line('Masukkan kode berikut di halaman yang sedang Anda buka:')
            ->line("# {$this->kode}")
            ->line('Kode berlaku '.KodeVerifikasi::BERLAKU_DETIK.' detik dan hanya bisa dipakai sekali. Jangan berikan kode ini kepada siapa pun.')
            ->line('Bila Anda tidak merasa meminta kode ini, abaikan email ini.')
            ->salutation('Salam, Admin LSP');
    }
}
