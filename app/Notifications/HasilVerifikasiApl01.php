<?php

namespace App\Notifications;

use App\Enums\StatusPermohonan;
use App\Models\PermohonanSertifikasi;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Hasil verifikasi APL-01 untuk calon asesi, dikirim otomatis begitu verifikator memutuskan.
 */
class HasilVerifikasiApl01 extends Notification
{
    public function __construct(private readonly PermohonanSertifikasi $permohonan) {}

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
        $diterima = $p->status === StatusPermohonan::Diterima;

        $mail = (new MailMessage)
            ->subject($diterima
                ? "Permohonan Sertifikasi {$p->nomor} Diterima"
                : "Permohonan Sertifikasi {$p->nomor} Ditolak")
            ->greeting("Halo, {$p->dataDiri->nama_lengkap}")
            ->line($diterima
                ? 'Selamat! Permohonan sertifikasi Anda telah diverifikasi dan **diterima** sebagai peserta sertifikasi.'
                : 'Permohonan sertifikasi Anda telah diverifikasi dan **belum dapat diterima**.')
            ->line("**No. permohonan:** {$p->nomor}")
            ->line("**Skema:** {$p->skema->judul} ({$p->skema->nomor})")
            ->line("**Periode:** {$p->periode->nama}");

        if ($p->catatan_verifikasi) {
            $mail->line("**Catatan verifikator:** {$p->catatan_verifikasi}");
        }

        return $mail
            ->line($diterima
                ? 'Informasi jadwal asesmen akan disampaikan selanjutnya oleh admin LSP.'
                : 'Silakan perbaiki berkas sesuai catatan di atas, lalu ajukan permohonan baru selama periode pendaftaran masih dibuka.')
            ->action('Lihat Permohonan', route('sertifikasi.show', $p->id_permohonan))
            ->salutation('Salam, Admin LSP');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $p = $this->permohonan;
        $diterima = $p->status === StatusPermohonan::Diterima;

        return [
            'judul' => $diterima ? 'Permohonan diterima' : 'Permohonan ditolak',
            'pesan' => $diterima
                ? "Permohonan {$p->nomor} untuk skema {$p->skema->judul} diterima."
                : "Permohonan {$p->nomor} untuk skema {$p->skema->judul} ditolak. Lihat catatan verifikator.",
            'url' => route('sertifikasi.show', $p->id_permohonan, absolute: false),
            'tipe' => $diterima ? 'sukses' : 'gagal',
        ];
    }
}
