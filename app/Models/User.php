<?php

namespace App\Models;

use App\Enums\TujuanOtp;
use App\Enums\UserRole;
use App\Models\Concerns\PunyaKodeUrl;
use App\Support\KodeVerifikasi;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Semua menu di dalam aplikasi mewajibkan email terverifikasi (middleware `verified`).
 * Akun asesor memverifikasi lewat link aktivasi, akun admin dibuat dalam keadaan terverifikasi.
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PunyaKodeUrl;

    protected $primaryKey = 'id_user';

    /**
     * Tabel users hanya punya created_at yang diisi default database.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'password',
        'role',
        'must_change_password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Data asesor yang memakai akun ini, jika ada.
     *
     * @return HasOne<Asesor, $this>
     */
    public function asesor(): HasOne
    {
        return $this->hasOne(Asesor::class, 'id_user', 'id_user');
    }

    /**
     * Profil (data diri) milik akun asesi.
     *
     * @return HasOne<DataDiri, $this>
     */
    public function dataDiri(): HasOne
    {
        return $this->hasOne(DataDiri::class, 'id_user', 'id_user');
    }

    /**
     * Pemilik akun sudah membuktikan email-nya (kode verifikasi, link aktivasi asesor, atau reset password).
     */
    public function isVerified(): bool
    {
        return $this->email_verified_at !== null;
    }

    /**
     * Akun asesi yang dinonaktifkan pemiliknya (soft delete). Bisa diaktifkan kembali lewat OTP.
     */
    public function isNonaktif(): bool
    {
        return $this->nonaktif_pada !== null;
    }

    /**
     * Nama yang ditampilkan di menu profil. Tabel users tidak menyimpan nama, jadi diambil
     * dari data asesor/asesi; akun admin memakai label perannya.
     */
    public function namaTampilan(): string
    {
        $nama = match ($this->role) {
            UserRole::Asesor => $this->asesor()->value('nama'),
            UserRole::Asesi => $this->dataDiri()->value('nama_lengkap'),
            default => null,
        };

        return $nama ?: $this->role?->label() ?? $this->email;
    }

    /**
     * Verifikasi email memakai kode OTP, termasuk saat dipicu event Registered bawaan Laravel.
     */
    public function sendEmailVerificationNotification(): void
    {
        KodeVerifikasi::kirim($this, TujuanOtp::VerifikasiEmail);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'role' => UserRole::class,
            'must_change_password' => 'boolean',
            'email_verified_at' => 'datetime',
            'nonaktif_pada' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
