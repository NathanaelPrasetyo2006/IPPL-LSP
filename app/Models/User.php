<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifikasiEmailAsesi;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Verifikasi email hanya diwajibkan (middleware `verified`) pada menu asesi;
 * akun asesor punya alur verifikasi sendiri dan akun admin dibuat langsung.
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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
     * Pemilik akun sudah membuktikan email-nya (lewat link verifikasi atau reset password).
     */
    public function isVerified(): bool
    {
        return $this->email_verified_at !== null;
    }

    /**
     * Kirim email reset password berbahasa Indonesia.
     *
     * @param  string  $token
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /**
     * Kirim email verifikasi berbahasa Indonesia.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifikasiEmailAsesi);
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
            'created_at' => 'datetime',
        ];
    }
}
