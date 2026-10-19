<?php

namespace App\Models;

use Database\Factories\AsesorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Asesor extends Model
{
    /** @use HasFactory<AsesorFactory> */
    use HasFactory;

    public const JENIS_KELAMIN = [
        'L' => 'Laki-laki',
        'P' => 'Perempuan',
    ];

    public const PENDIDIKAN = [
        'SMA/SMK' => 'SMA/SMK',
        'D3' => 'Diploma 3 (D3)',
        'D4' => 'Diploma 4 (D4)',
        'S1' => 'Sarjana (S1)',
        'S2' => 'Magister (S2)',
        'S3' => 'Doktor (S3)',
    ];

    /**
     * Biodata yang wajib terisi sebelum Kartu Tanda Asesor bisa dibuat.
     *
     * @var list<string>
     */
    public const BIODATA_WAJIB = [
        'nik',
        'tempat_lahir',
        'tgl_lahir',
        'jenis_kelamin',
        'no_telp',
        'alamat',
        'kota',
        'provinsi',
        'pendidikan_terakhir',
        'bidang_keahlian',
        'masa_berlaku_sertifikat',
        'file_pas_foto',
    ];

    protected $table = 'asesor';

    protected static function booted(): void
    {
        // Kode asesor diturunkan dari id supaya pasti unik tanpa perlu mengunci tabel.
        static::created(function (Asesor $asesor) {
            $asesor->forceFill([
                'kode_asesor' => sprintf('ASR-%s-%05d', now()->year, $asesor->id_asesor),
            ])->saveQuietly();
        });
    }

    protected $primaryKey = 'id_asesor';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id_user',
        'nama',
        'no_reg',
        'nik',
        'tempat_lahir',
        'tgl_lahir',
        'jenis_kelamin',
        'no_telp',
        'alamat',
        'kota',
        'provinsi',
        'pendidikan_terakhir',
        'instansi',
        'bidang_keahlian',
        'masa_berlaku_sertifikat',
        'file_pas_foto',
        'biodata_diperbarui_pada',
    ];

    /**
     * Akun login milik asesor ini.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    /**
     * Semua biodata wajib sudah terisi, termasuk pas foto.
     */
    public function biodataLengkap(): bool
    {
        foreach (self::BIODATA_WAJIB as $kolom) {
            if (blank($this->{$kolom})) {
                return false;
            }
        }

        return true;
    }

    /**
     * Sertifikat asesor (MET) sudah melewati masa berlakunya.
     */
    public function sertifikatKedaluwarsa(): bool
    {
        return $this->masa_berlaku_sertifikat !== null && $this->masa_berlaku_sertifikat->lt(today());
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tgl_lahir' => 'date',
            'masa_berlaku_sertifikat' => 'date',
            'biodata_diperbarui_pada' => 'datetime',
        ];
    }
}
