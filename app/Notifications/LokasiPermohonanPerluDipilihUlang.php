<?php

namespace App\Notifications;

use App\Models\PermohonanSertifikasi;
use App\Models\Tuk;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Lokasi uji pilihan asesi dihapus admin dari periode, jadi asesi harus memilih lokasi lain.
 */
class LokasiPermohonanPerluDipilihUlang extends Notification
{
    public function __construct(
        private readonly PermohonanSertifikasi $permohonan,
        private readonly Tuk $lokasiLama,
    ) {}

    /**
     * Database didahulukan supaya pemberitahuan di aplikasi tetap tersimpan
     * walaupun pengiriman email gagal.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $p = $this->permohonan;

        return (new MailMessage)
            ->subject("Pilih Ulang Lokasi Uji Permohonan {$p->nomor}")
            ->greeting("Halo, {$p->dataDiri->nama_lengkap}")
            ->line("Lokasi uji **{$this->lokasiLama->nama}** tidak lagi tersedia untuk skema yang Anda daftarkan.")
            ->line("**No. permohonan:** {$p->nomor}")
            ->line("**Skema:** {$p->skema->judul} ({$p->skema->nomor})")
            ->line("**Periode:** {$p->periode->nama}")
            ->line('Silakan buka permohonan Anda dan pilih lokasi uji yang baru.')
            ->action('Pilih Lokasi Baru', route('sertifikasi.show', $p->id_permohonan))
            ->salutation('Salam, Admin LSP');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'judul' => 'Pilih ulang lokasi uji',
            'pesan' => "Lokasi {$this->lokasiLama->nama} untuk permohonan {$this->permohonan->nomor} tidak lagi tersedia. Silakan pilih lokasi baru.",
            'url' => route('sertifikasi.show', $this->permohonan->id_permohonan, absolute: false),
            'tipe' => 'peringatan',
        ];
    }
}
