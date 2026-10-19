<?php

namespace App\Models;

use App\Enums\StatusPermohonan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu perubahan status permohonan APL-01 beserta catatannya.
 */
class RiwayatStatusPermohonan extends Model
{
    protected $table = 'riwayat_status_permohonan';

    protected $primaryKey = 'id_riwayat_status';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'status',
        'catatan',
        'id_user',
    ];

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
            'status' => StatusPermohonan::class,
            'created_at' => 'datetime',
        ];
    }
}
