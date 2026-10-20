<?php

namespace App\Models;

use App\Models\Concerns\PunyaKodeUrl;
use Database\Factories\TukFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tuk extends Model
{
    /** @use HasFactory<TukFactory> */
    use HasFactory, PunyaKodeUrl, SoftDeletes;

    protected $table = 'tuk';

    protected $primaryKey = 'id_tuk';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nama',
        'alamat',
        'provinsi',
        'kota',
        'kecamatan',
        'kelurahan',
    ];

    /**
     * Kolom yang membentuk alamat lengkap, urut dari detail jalan sampai provinsi.
     *
     * @var list<string>
     */
    public const KOLOM_ALAMAT = ['alamat', 'kelurahan', 'kecamatan', 'kota', 'provinsi'];

    /**
     * Detail jalan digabung dengan wilayahnya, mis. "Jl. Pemuda 1, Embong Kaliasin, Genteng, Kota Surabaya, Jawa Timur".
     */
    public function alamatLengkap(): string
    {
        return collect(self::KOLOM_ALAMAT)->map(fn (string $kolom) => $this->{$kolom})->filter()->implode(', ');
    }

    /**
     * Data lokasi untuk ditampilkan; "alamat" di sini sudah berupa alamat lengkap.
     *
     * @return array{id_tuk: int, ulid: string, nama: string, alamat: string}
     */
    public function ringkas(): array
    {
        return [
            'id_tuk' => $this->id_tuk,
            'ulid' => $this->ulid,
            'nama' => $this->nama,
            'alamat' => $this->alamatLengkap(),
        ];
    }
}
