<?php

namespace App\Models;

use App\Enums\StatusPermohonan;
use Database\Factories\PeriodePendaftaranFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
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
     * Lokasi TUK periode ini. Satu TUK muncul sekali per skema yang diujikan di sana (lihat pivot id_skema).
     *
     * @return BelongsToMany<Tuk, $this>
     */
    public function tuk(): BelongsToMany
    {
        return $this->belongsToMany(Tuk::class, 'periode_lokasi', 'id_periode', 'id_tuk')->withPivot('id_skema');
    }

    /**
     * Lokasi beserta skemanya dalam bentuk isian form.
     *
     * @return list<array{id_tuk: string, skema: list<int>}>
     */
    public function lokasiForm(): array
    {
        return $this->tuk()
            ->orderBy('nama')
            ->get()
            ->groupBy('id_tuk')
            ->map(fn ($baris, $idTuk) => [
                'id_tuk' => (string) $idTuk,
                'skema' => $baris->map(fn (Tuk $tuk) => (int) $tuk->pivot->id_skema)->values()->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * Ganti seluruh lokasi periode. Skema periode harus sudah tersinkron lebih dulu.
     *
     * @param  list<array{id_tuk: int|string, skema: list<int|string>}>  $lokasi
     */
    public function simpanLokasi(array $lokasi): void
    {
        $this->tuk()->detach();

        foreach ($lokasi as $item) {
            foreach (array_unique($item['skema']) as $idSkema) {
                $this->tuk()->attach((int) $item['id_tuk'], ['id_skema' => (int) $idSkema]);
            }
        }
    }

    /**
     * Lokasi yang tersedia untuk satu skema pada periode ini.
     *
     * @return Collection<int, Tuk>
     */
    public function lokasiSkema(int $idSkema): Collection
    {
        return $this->tuk()->wherePivot('id_skema', $idSkema)->orderBy('nama')->get(['tuk.id_tuk', 'tuk.nama', 'tuk.alamat']);
    }

    /**
     * Kosongkan lokasi permohonan berjalan yang lokasinya sudah tidak tersedia untuk skemanya,
     * supaya asesi memilih ulang. Mengembalikan permohonan terdampak beserta lokasi lamanya.
     *
     * @return list<array{permohonan: PermohonanSertifikasi, lokasi_lama: Tuk}>
     */
    public function kosongkanLokasiTidakTersedia(?User $oleh): array
    {
        $terdampak = $this->permohonan()
            ->with('tuk')
            ->whereIn('status', StatusPermohonan::aktif())
            ->whereNotNull('id_tuk')
            ->whereNotExists(fn ($query) => $query->selectRaw('1')
                ->from('periode_lokasi')
                ->join('tuk', 'tuk.id_tuk', '=', 'periode_lokasi.id_tuk')
                ->whereNull('tuk.deleted_at')
                ->whereColumn('periode_lokasi.id_periode', 'permohonan_sertifikasi.id_periode')
                ->whereColumn('periode_lokasi.id_skema', 'permohonan_sertifikasi.id_skema')
                ->whereColumn('periode_lokasi.id_tuk', 'permohonan_sertifikasi.id_tuk'))
            ->get();

        return $terdampak->map(function (PermohonanSertifikasi $permohonan) use ($oleh) {
            $lokasiLama = $permohonan->tuk;

            $permohonan->gantiLokasi(null, "Lokasi {$lokasiLama->nama} dihapus dari skema pada periode ini oleh admin LSP.", $oleh);

            return ['permohonan' => $permohonan, 'lokasi_lama' => $lokasiLama];
        })->all();
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
