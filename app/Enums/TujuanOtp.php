<?php

namespace App\Enums;

/**
 * Keperluan kode OTP yang dikirim ke email.
 */
enum TujuanOtp: string
{
    case ResetPassword = 'reset_password';
    case AktivasiAkun = 'aktivasi_akun';
    case GantiEmail = 'ganti_email';
    case VerifikasiEmail = 'verifikasi_email';

    /**
     * Dipakai sebagai subjek email dan kalimat pembuka.
     */
    public function label(): string
    {
        return match ($this) {
            self::ResetPassword => 'Reset Password',
            self::AktivasiAkun => 'Aktivasi Ulang Akun',
            self::GantiEmail => 'Verifikasi Email Baru',
            self::VerifikasiEmail => 'Verifikasi Email',
        };
    }

    public function keterangan(): string
    {
        return match ($this) {
            self::ResetPassword => 'Kami menerima permintaan untuk mengatur ulang password akun LSP Anda.',
            self::AktivasiAkun => 'Kami menerima permintaan untuk mengaktifkan kembali akun LSP Anda.',
            self::GantiEmail => 'Alamat email ini didaftarkan sebagai email baru untuk akun LSP Anda.',
            self::VerifikasiEmail => 'Terima kasih telah mendaftar sebagai calon asesi. Verifikasi email Anda untuk mulai memakai akun.',
        };
    }
}
