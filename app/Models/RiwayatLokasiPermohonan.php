<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu perubahan lokasi uji pada permohonan APL-01.
 */
class RiwayatLokasiPermohonan extends Model
{
    protected $table = 'riwayat_lokasi_permohonan';

    protected $primaryKey = 'id_riwayat';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id_tuk_lama',
        'id_tuk_baru',
        'keterangan',
        'id_user',
    ];

    /**
     * @return BelongsTo<Tuk, $this>
     */
    public function tukLama(): BelongsTo
    {
        return $this->belongsTo(Tuk::class, 'id_tuk_lama', 'id_tuk')->withTrashed();
    }

    /**
     * @return BelongsTo<Tuk, $this>
     */
    public function tukBaru(): BelongsTo
    {
        return $this->belongsTo(Tuk::class, 'id_tuk_baru', 'id_tuk')->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }
}
