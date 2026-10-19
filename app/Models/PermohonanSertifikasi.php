<?php

namespace App\Models;

use App\Enums\StatusPermohonan;
use App\Enums\TujuanAsesmen;
use App\Models\Concerns\PunyaKodeUrl;
use Database\Factories\PermohonanSertifikasiFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Formulir FR-APL-01 (Permohonan Sertifikasi Kompetensi).
 */
class PermohonanSertifikasi extends Model
{
    /** @use HasFactory<PermohonanSertifikasiFactory> */
    use HasFactory, PunyaKodeUrl;

    protected $table = 'permohonan_sertifikasi';

    protected $primaryKey = 'id_permohonan';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id_data_diri',
        'id_periode',
        'id_skema',
        'id_tuk',
        'tujuan_asesmen',
        'tujuan_asesmen_lainnya',
        'status',
        'cek_dokumen',
        'catatan_verifikasi',
        'id_verifikator',
        'diverifikasi_pada',
    ];

    protected static function booted(): void
    {
        // Nomor formulir diturunkan dari id supaya pasti unik tanpa perlu mengunci tabel.
        static::created(function (PermohonanSertifikasi $permohonan) {
            $permohonan->forceFill([
                'nomor' => sprintf('APL-%s-%05d', $permohonan->created_at?->year ?? now()->year, $permohonan->id_permohonan),
            ])->saveQuietly();
        });
    }

    /**
     * @return BelongsTo<DataDiri, $this>
     */
    public function dataDiri(): BelongsTo
    {
        return $this->belongsTo(DataDiri::class, 'id_data_diri', 'id_data_diri');
    }

    /**
     * @return BelongsTo<PeriodePendaftaran, $this>
     */
    public function periode(): BelongsTo
    {
        return $this->belongsTo(PeriodePendaftaran::class, 'id_periode', 'id_periode');
    }

    /**
     * Skema tetap ditampilkan walau kemudian dihapus (soft delete) dari master data.
     *
     * @return BelongsTo<SkemaSertifikasi, $this>
     */
    public function skema(): BelongsTo
    {
        return $this->belongsTo(SkemaSertifikasi::class, 'id_skema', 'id_skema')->withTrashed();
    }

    /**
     * Lokasi uji pilihan asesi; tetap ditampilkan walau TUK-nya kemudian dihapus dari master data.
     *
     * @return BelongsTo<Tuk, $this>
     */
    public function tuk(): BelongsTo
    {
        return $this->belongsTo(Tuk::class, 'id_tuk', 'id_tuk')->withTrashed();
    }

    /**
     * @return HasMany<RiwayatLokasiPermohonan, $this>
     */
    public function riwayatLokasi(): HasMany
    {
        return $this->hasMany(RiwayatLokasiPermohonan::class, 'id_permohonan', 'id_permohonan')->orderBy('id_riwayat');
    }

    /**
     * @return HasMany<RiwayatStatusPermohonan, $this>
     */
    public function riwayatStatus(): HasMany
    {
        return $this->hasMany(RiwayatStatusPermohonan::class, 'id_permohonan', 'id_permohonan')->orderBy('id_riwayat_status');
    }

    /**
     * Catat perubahan status ke riwayat. Status di tabel permohonan diubah oleh pemanggil.
     */
    public function catatStatus(StatusPermohonan $status, ?string $catatan, ?User $oleh): void
    {
        $this->riwayatStatus()->create([
            'status' => $status,
            'catatan' => $catatan,
            'id_user' => $oleh?->id_user,
        ]);
    }

    /**
     * Ganti lokasi uji sekaligus mencatat riwayatnya. Null mengosongkan lokasi (asesi perlu memilih ulang).
     */
    public function gantiLokasi(?int $idTuk, string $keterangan, ?User $oleh): void
    {
        $this->riwayatLokasi()->create([
            'id_tuk_lama' => $this->id_tuk,
            'id_tuk_baru' => $idTuk,
            'keterangan' => $keterangan,
            'id_user' => $oleh?->id_user,
        ]);

        $this->update(['id_tuk' => $idTuk]);
    }

    /**
     * Permohonan yang masih berjalan tetapi belum punya lokasi uji.
     */
    public function perluPilihLokasi(): bool
    {
        return $this->id_tuk === null && in_array($this->status->value, StatusPermohonan::aktif(), true);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_verifikator', 'id_user');
    }

    /**
     * @return HasMany<DokumenPermohonan, $this>
     */
    public function dokumen(): HasMany
    {
        return $this->hasMany(DokumenPermohonan::class, 'id_permohonan', 'id_permohonan');
    }

    /**
     * Folder dokumen khusus permohonan ini, di dalam folder asesi.
     */
    public function folderDokumen(): string
    {
        return $this->dataDiri->folderDokumen()."/apl-01/{$this->nomor}";
    }

    public function sudahDiputuskan(): bool
    {
        return $this->status !== StatusPermohonan::Diajukan;
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
            'tujuan_asesmen' => TujuanAsesmen::class,
            'cek_dokumen' => 'array',
            'diverifikasi_pada' => 'datetime',
        ];
    }
}
