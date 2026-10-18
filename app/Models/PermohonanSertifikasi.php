<?php

namespace App\Models;

use App\Enums\StatusPermohonan;
use App\Enums\TujuanAsesmen;
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
    use HasFactory;

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
