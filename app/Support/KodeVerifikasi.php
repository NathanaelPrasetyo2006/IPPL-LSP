<?php

namespace App\Support;

use App\Enums\TujuanOtp;
use App\Models\KodeOtp;
use App\Models\User;
use App\Notifications\KodeOtpNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

/**
 * Kirim dan periksa kode OTP 6 digit lewat email.
 * Error selalu dilempar pada field "kode" supaya bisa ditampilkan langsung di bawah isian kode.
 */
class KodeVerifikasi
{
    public const BERLAKU_DETIK = 60;

    public const JEDA_KIRIM_ULANG_DETIK = 60;

    public const MAKS_KIRIM_ULANG_PER_HARI = 5;

    public const MAKS_PERCOBAAN = 5;

    /** Batas harian dihitung per tanggal WIB, bukan UTC. */
    private const ZONA_WAKTU = 'Asia/Jakarta';

    /**
     * Buat kode baru dan kirim ke email akun (atau ke $email, mis. untuk verifikasi email baru).
     *
     * @throws ValidationException bila masih dalam jeda kirim ulang atau batas harian habis
     */
    public static function kirim(User $user, TujuanOtp $tujuan, ?string $email = null): void
    {
        $otp = KodeOtp::firstOrNew(['id_user' => $user->id_user, 'tujuan' => $tujuan]);
        $jumlahHariIni = self::jumlahKirimHariIni($otp);

        if (($tunggu = self::sisaDetik($otp->terakhir_kirim_pada?->copy()->addSeconds(self::JEDA_KIRIM_ULANG_DETIK))) > 0) {
            throw ValidationException::withMessages(['kode' => "Tunggu {$tunggu} detik sebelum meminta kode baru."]);
        }

        // Satu kali kirim pertama ditambah batas kirim ulang.
        if ($jumlahHariIni >= self::MAKS_KIRIM_ULANG_PER_HARI + 1) {
            throw ValidationException::withMessages([
                'kode' => 'Batas kirim ulang kode hari ini ('.self::MAKS_KIRIM_ULANG_PER_HARI.' kali) sudah habis. Silakan coba lagi besok.',
            ]);
        }

        $kode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $otp->fill([
            'kode_hash' => Hash::make($kode),
            'kedaluwarsa_pada' => now()->addSeconds(self::BERLAKU_DETIK),
            'percobaan_salah' => 0,
            'tanggal_kirim' => now(self::ZONA_WAKTU)->toDateString(),
            'jumlah_kirim' => $jumlahHariIni + 1,
            'terakhir_kirim_pada' => now(),
        ])->save();

        $notifikasi = new KodeOtpNotification($kode, $tujuan);

        $email !== null
            ? Notification::route('mail', $email)->notify($notifikasi)
            : $user->notify($notifikasi);
    }

    /**
     * Cocokkan kode. Bila benar, kode langsung hangus supaya tidak bisa dipakai dua kali.
     *
     * @throws ValidationException bila kode salah, kedaluwarsa, atau terlalu sering salah
     */
    public static function periksa(User $user, TujuanOtp $tujuan, string $kode): void
    {
        $otp = KodeOtp::query()->where('id_user', $user->id_user)->where('tujuan', $tujuan)->first();

        if (! $otp?->kode_hash) {
            throw ValidationException::withMessages(['kode' => 'Kode belum dikirim atau sudah tidak berlaku. Silakan minta kode baru.']);
        }

        if ($otp->kedaluwarsa_pada->isPast()) {
            throw ValidationException::withMessages(['kode' => 'Kode sudah kedaluwarsa. Silakan minta kode baru.']);
        }

        if (! Hash::check($kode, $otp->kode_hash)) {
            $otp->increment('percobaan_salah');
            $sisa = self::MAKS_PERCOBAAN - $otp->percobaan_salah;

            // Kode dihanguskan setelah terlalu sering salah, untuk mencegah tebak-tebakan.
            if ($sisa <= 0) {
                $otp->update(['kode_hash' => null, 'kedaluwarsa_pada' => null]);

                throw ValidationException::withMessages(['kode' => 'Kode salah '.self::MAKS_PERCOBAAN.' kali. Silakan minta kode baru.']);
            }

            throw ValidationException::withMessages(['kode' => "Kode salah. Sisa {$sisa} kali percobaan."]);
        }

        $otp->update(['kode_hash' => null, 'kedaluwarsa_pada' => null]);
    }

    /**
     * Hanguskan kode yang masih berlaku, mis. saat pengguna membatalkan ganti email.
     */
    public static function batalkan(User $user, TujuanOtp $tujuan): void
    {
        KodeOtp::query()
            ->where('id_user', $user->id_user)
            ->where('tujuan', $tujuan)
            ->update(['kode_hash' => null, 'kedaluwarsa_pada' => null]);
    }

    /**
     * Keadaan kode untuk hitung mundur di halaman.
     *
     * @return array{terkirim: bool, kedaluwarsa_dalam: int, kirim_ulang_dalam: int, sisa_kirim_ulang: int}
     */
    public static function status(User $user, TujuanOtp $tujuan): array
    {
        $otp = KodeOtp::query()->where('id_user', $user->id_user)->where('tujuan', $tujuan)->first();
        $jumlahHariIni = $otp ? self::jumlahKirimHariIni($otp) : 0;

        return [
            'terkirim' => (bool) $otp?->kode_hash,
            'kedaluwarsa_dalam' => $otp?->kode_hash ? self::sisaDetik($otp->kedaluwarsa_pada) : 0,
            'kirim_ulang_dalam' => self::sisaDetik($otp?->terakhir_kirim_pada?->copy()->addSeconds(self::JEDA_KIRIM_ULANG_DETIK)),
            'sisa_kirim_ulang' => max(0, min(self::MAKS_KIRIM_ULANG_PER_HARI, self::MAKS_KIRIM_ULANG_PER_HARI + 1 - $jumlahHariIni)),
        ];
    }

    /**
     * Tampilkan email tanpa membuka seluruh alamatnya: 2 huruf depan dan 2 huruf belakang
     * nama email tetap terlihat, mis. "budisantoso@gmail.com" menjadi "bu*******so@gmail.com".
     */
    public static function samarkan(string $email): string
    {
        [$nama, $domain] = array_pad(explode('@', $email, 2), 2, '');
        $panjang = mb_strlen($nama);

        // Nama sependek ini akan terbuka seluruhnya bila 2+2 huruf ditampilkan, jadi cukup huruf pertamanya.
        if ($panjang <= 4) {
            return mb_substr($nama, 0, 1).str_repeat('*', 3).'@'.$domain;
        }

        return mb_substr($nama, 0, 2).str_repeat('*', $panjang - 4).mb_substr($nama, -2).'@'.$domain;
    }

    private static function jumlahKirimHariIni(KodeOtp $otp): int
    {
        return $otp->tanggal_kirim?->toDateString() === now(self::ZONA_WAKTU)->toDateString() ? $otp->jumlah_kirim : 0;
    }

    private static function sisaDetik(?Carbon $sampai): int
    {
        return $sampai ? max(0, (int) ceil(now()->diffInSeconds($sampai, false))) : 0;
    }
}
