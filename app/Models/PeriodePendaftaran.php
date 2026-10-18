<?php

namespace App\Models;

use App\Enums\StatusPermohonan;
use Database\Factories\PeriodePendaftaranFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PeriodePendaftaran extends Model
{
    /** @use HasFactory<PeriodePendaftaranFactory> */
    use HasFactory;

    protected $table = 'periode_pendaftaran';

    protected $primaryKey = 'id_periode';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nama',
        'tanggal_buka',
        'tanggal_tutup',
        'kuota',
        'keterangan',
        'aktif',
    ];

    /**
     * Skema yang dibuka pada periode ini.
     *
     * @return BelongsToMany<SkemaSertifikasi, $this>
     */
    public function skema(): BelongsToMany
    {
        return $this->belongsToMany(SkemaSertifikasi::class, 'periode_skema', 'id_periode', 'id_skema');
    }

    /**
     * @return HasMany<PermohonanSertifikasi, $this>
     */
    public function permohonan(): HasMany
    {
        return $this->hasMany(PermohonanSertifikasi::class, 'id_periode', 'id_periode');
    }

    /**
     * Periode yang sedang menerima pendaftaran hari ini.
     *
     * @param  Builder<PeriodePendaftaran>  $query
     */
    public function scopeDibuka(Builder $query): void
    {
        $query->where('aktif', true)
            ->whereDate('tanggal_buka', '<=', today())
            ->whereDate('tanggal_tutup', '>=', today());
    }

    /**
     * dibuka | dijadwalkan | ditutup
     */
    public function status(): string
    {
        return match (true) {
            ! $this->aktif || $this->tanggal_tutup->lt(today()) => 'ditutup',
            $this->tanggal_buka->gt(today()) => 'dijadwalkan',
            default => 'dibuka',
        };
    }

    /**
     * Jumlah permohonan yang memakai kuota (yang ditolak tidak dihitung).
     */
    public function kuotaTerpakai(): int
    {
        return $this->permohonan()->whereIn('status', StatusPermohonan::aktif())->count();
    }

    public function kuotaPenuh(): bool
    {
        return $this->kuota !== null && $this->kuotaTerpakai() >= $this->kuota;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_buka' => 'date',
            'tanggal_tutup' => 'date',
            'kuota' => 'integer',
            'aktif' => 'boolean',
        ];
    }
}
