<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnitKompetensi extends Model
{
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
