<?php

namespace App\Models;

use App\Enums\JenisDokumen;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bukti persyaratan dasar yang diunggah untuk satu permohonan APL-01.
 */
class DokumenPermohonan extends Model
{
    protected $table = 'dokumen_permohonan';

    protected $primaryKey = 'id_dokumen';

    public const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id_permohonan',
        'jenis',
        'nama_asli',
        'path',
        'mime',
        'ukuran',
    ];

    /**
     * @return BelongsTo<PermohonanSertifikasi, $this>
     */
    public function permohonan(): BelongsTo
    {
        return $this->belongsTo(PermohonanSertifikasi::class, 'id_permohonan', 'id_permohonan');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jenis' => JenisDokumen::class,
            'ukuran' => 'integer',
        ];
    }
}
