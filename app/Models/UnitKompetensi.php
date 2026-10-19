<?php

namespace App\Models;

use App\Models\Concerns\PunyaKodeUrl;
use Database\Factories\UnitKompetensiFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class UnitKompetensi extends Model
{
    /** @use HasFactory<UnitKompetensiFactory> */
    use HasFactory, PunyaKodeUrl, SoftDeletes;

    protected $table = 'unit_kompetensi';

    protected $primaryKey = 'id_unit_kompetensi';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id_skema',
        'kode',
        'judul',
        'urutan',
    ];

    /**
     * Skema sertifikasi yang menaungi unit ini.
     *
     * @return BelongsTo<SkemaSertifikasi, $this>
     */
    public function skema(): BelongsTo
    {
        return $this->belongsTo(SkemaSertifikasi::class, 'id_skema', 'id_skema');
    }
}
