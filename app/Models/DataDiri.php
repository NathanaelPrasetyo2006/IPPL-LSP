<?php

namespace App\Models;

use App\Enums\JenisDokumen;
use App\Support\PenyimpananDokumen;
use Database\Factories\DataDiriFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Profil calon asesi (Bagian 1 FR-APL-01). Dibuat saat registrasi lalu dilengkapi
 * dan diperbarui setiap kali asesi mengajukan permohonan sertifikasi.
 */
class DataDiri extends Model
{
    /** @use HasFactory<DataDiriFactory> */
    use HasFactory;

    public const JENIS_KELAMIN = [
        'L' => 'Laki-laki',
        'P' => 'Perempuan',
    ];

    public const PENDIDIKAN = [
        'SD' => 'SD',
        'SMP' => 'SMP',
        'SMA/SMK' => 'SMA/SMK',
        'D1' => 'Diploma 1 (D1)',
        'D2' => 'Diploma 2 (D2)',
        'D3' => 'Diploma 3 (D3)',
        'D4' => 'Diploma 4 (D4)',
        'S1' => 'Sarjana (S1)',
        'S2' => 'Magister (S2)',
        'S3' => 'Doktor (S3)',
    ];

    protected $table = 'data_diri';

    protected $primaryKey = 'id_data_diri';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id_user',
        'nama_lengkap',
        'no_telp',
        'nik',
        'tempat_lahir',
        'tgl_lahir',
        'jenis_kelamin',
        'id_negara',
        'pekerjaan',
        'alamat_ktp',
        'provinsi_ktp',
        'kota_ktp',
        'kecamatan_ktp',
        'kelurahan_ktp',
        'alamat_domisili',
        'provinsi',
        'kota',
        'kecamatan',
        'kelurahan',
        'kode_pos',
        'jenjang_pendidikan',
        'nama_sekolah',
        'nama_institusi',
        'jabatan',
        'alamat_kantor',
        'provinsi_kantor',
        'kota_kantor',
        'kecamatan_kantor',
        'kelurahan_kantor',
        'kode_pos_kantor',
        'telp_kantor',
        'email_kantor',
        'file_ktp',
        'file_pas_foto',
        'file_npwp',
        'file_cv',
    ];

    protected static function booted(): void
    {
        // Kode asesi diturunkan dari id supaya pasti unik tanpa perlu mengunci tabel.
        static::created(function (DataDiri $dataDiri) {
            $dataDiri->forceFill([
                'kode_asesi' => sprintf('ASI-%s-%05d', $dataDiri->created_at?->year ?? now()->year, $dataDiri->id_data_diri),
            ])->saveQuietly();
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    /**
     * @return BelongsTo<Negara, $this>
     */
    public function negara(): BelongsTo
    {
        return $this->belongsTo(Negara::class, 'id_negara', 'id_negara');
    }

    /**
     * @return HasMany<PermohonanSertifikasi, $this>
     */
    public function permohonan(): HasMany
    {
        return $this->hasMany(PermohonanSertifikasi::class, 'id_data_diri', 'id_data_diri');
    }

    /**
     * Dokumen terbaru per jenis dari permohonan sebelumnya yang boleh dipakai ulang
     * (lihat JenisDokumen::dipakaiUlang()), selain milik $kecuali. Hanya yang file-nya masih ada.
     *
     * @return Collection<string, DokumenPermohonan>
     */
    public function dokumenDipakaiUlang(?PermohonanSertifikasi $kecuali = null): Collection
    {
        $jenis = array_map(fn (JenisDokumen $j) => $j->value, array_filter(JenisDokumen::cases(), fn (JenisDokumen $j) => $j->dipakaiUlang()));

        return DokumenPermohonan::query()
            ->whereIn('jenis', $jenis)
            ->whereHas('permohonan', fn ($query) => $query
                ->where('id_data_diri', $this->id_data_diri)
                ->when($kecuali, fn ($query) => $query->whereKeyNot($kecuali->id_permohonan)))
            ->with('permohonan')
            ->orderByDesc('id_dokumen')
            ->get()
            ->filter(fn (DokumenPermohonan $dokumen) => Storage::disk(PenyimpananDokumen::DISK)->exists($dokumen->path))
            ->unique(fn (DokumenPermohonan $dokumen) => $dokumen->jenis->value)
            ->keyBy(fn (DokumenPermohonan $dokumen) => $dokumen->jenis->value);
    }

    /**
     * Folder dokumen asesi di disk privat, mis. "asesi/ASI-2026-00001_budi-santoso".
     * Ditetapkan sekali lalu disimpan, sehingga perubahan nama tidak memecah folder.
     */
    public function folderDokumen(): string
    {
        if (! $this->folder_dokumen) {
            $nama = Str::slug($this->nama_lengkap) ?: 'asesi';

            $this->forceFill(['folder_dokumen' => "asesi/{$this->kode_asesi}_".Str::limit($nama, 60, '')])->save();
        }

        return $this->folder_dokumen;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tgl_lahir' => 'date',
        ];
    }
}
