<?php

namespace App\Notifications;

use App\Models\PermohonanSertifikasi;
use Illuminate\Notifications\Notification;

/**
 * Pemberitahuan di aplikasi untuk admin bahwa ada permohonan APL-01 yang perlu diverifikasi.
 */
class PermohonanApl01Masuk extends Notification
{
    public function __construct(private readonly PermohonanSertifikasi $permohonan) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'judul' => 'Permohonan APL-01 baru',
            'pesan' => "{$this->permohonan->dataDiri->nama_lengkap} mengajukan skema {$this->permohonan->skema->judul} ({$this->permohonan->nomor}).",
            'url' => route('pendaftaran.verifikasi.show', $this->permohonan->id_permohonan, absolute: false),
            'tipe' => 'info',
        ];
    }
}
