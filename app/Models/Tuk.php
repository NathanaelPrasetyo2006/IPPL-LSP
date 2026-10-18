<?php

namespace App\Models;

use Database\Factories\TukFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tuk extends Model
{
    /** @use HasFactory<TukFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Jenis TUK yang diakui, mengikuti pembagian BNSP.
     *
     * @var array<string, string>
     */
    public const JENIS = [
        'sewaktu' => 'TUK Sewaktu',
        'tempat_kerja' => 'TUK Tempat Kerja',
        'mandiri' => 'TUK Mandiri',
    ];

    /**
     * @var array<string, string>
     */
    public const STATUS = [
        'aktif' => 'Aktif',
        'nonaktif' => 'Non-Aktif',
    ];

    protected $table = 'tuk';

    protected $primaryKey = 'id_tuk';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'kode',
        'nama',
        'jenis',
        'alamat',
        'kota',
        'penanggung_jawab',
        'telepon',
        'status',
    ];
}
