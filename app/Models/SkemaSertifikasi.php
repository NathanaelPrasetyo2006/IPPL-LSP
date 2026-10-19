<?php

namespace App\Models;

use App\Models\Concerns\PunyaKodeUrl;
use Database\Factories\SkemaSertifikasiFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SkemaSertifikasi extends Model
{
    /** @use HasFactory<SkemaSertifikasiFactory> */
    use HasFactory, PunyaKodeUrl, SoftDeletes;

    protected $table = 'skema_sertifikasi';

    protected $primaryKey = 'id_skema';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'judul',
        'nomor',
    ];

    /**
     * Unit kompetensi yang tergabung dalam skema ini.
     *
     * @return HasMany<UnitKompetensi, $this>
     */
    public function unitKompetensi(): HasMany
    {
        return $this->hasMany(UnitKompetensi::class, 'id_skema', 'id_skema');
    }
}
