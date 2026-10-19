<?php

namespace App\Models;

use App\Enums\TujuanOtp;
use Illuminate\Database\Eloquent\Model;

class KodeOtp extends Model
{
    protected $table = 'kode_otp';

    protected $primaryKey = 'id_kode_otp';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id_user',
        'tujuan',
        'kode_hash',
        'kedaluwarsa_pada',
        'percobaan_salah',
        'tanggal_kirim',
        'jumlah_kirim',
        'terakhir_kirim_pada',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tujuan' => TujuanOtp::class,
            'kedaluwarsa_pada' => 'datetime',
            'tanggal_kirim' => 'date',
            'terakhir_kirim_pada' => 'datetime',
        ];
    }
}
