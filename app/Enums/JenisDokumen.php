<?php

namespace App\Enums;

/**
 * Dokumen pada Bagian 3 FR-APL-01.
 *
 * Bukti administratif (identitas) disimpan di data_diri karena dipakai ulang di
 * setiap permohonan; bukti persyaratan dasar disimpan per permohonan.
 */
enum JenisDokumen: string
{
    // Bukti administratif.
    case Ktp = 'ktp';
    case PasFoto = 'pas_foto';
    case Npwp = 'npwp';
    case Cv = 'cv';

    // Bukti persyaratan dasar.
    case Ijazah = 'ijazah';
    case Transkrip = 'transkrip';
    case SertifikatPelatihan = 'sertifikat_pelatihan';
    case SuratKeteranganKerja = 'surat_keterangan_kerja';
    case Portofolio = 'portofolio';

    public function label(): string
    {
        return match ($this) {
            self::Ktp => 'KTP / Paspor',
            self::PasFoto => 'Pas foto 3×4',
            self::Npwp => 'NPWP',
            self::Cv => 'Daftar riwayat hidup (CV)',
            self::Ijazah => 'Ijazah terakhir',
            self::Transkrip => 'Transkrip nilai',
            self::SertifikatPelatihan => 'Sertifikat pelatihan / kompetensi',
            self::SuratKeteranganKerja => 'Surat keterangan pengalaman kerja',
            self::Portofolio => 'Portofolio / bukti pendukung lain',
        };
    }

    public function wajib(): bool
    {
        return in_array($this, [self::Ktp, self::PasFoto, self::Ijazah], true);
    }

    /**
     * Dokumen identitas yang melekat ke profil asesi, bukan ke satu permohonan.
     */
    public function identitas(): bool
    {
        return $this->kolomDataDiri() !== null;
    }

    /**
     * Kolom penyimpan path di tabel data_diri, khusus dokumen identitas.
     */
    public function kolomDataDiri(): ?string
    {
        return match ($this) {
            self::Ktp => 'file_ktp',
            self::PasFoto => 'file_pas_foto',
            self::Npwp => 'file_npwp',
            self::Cv => 'file_cv',
            default => null,
        };
    }

    /**
     * @return list<string>
     */
    public function mimes(): array
    {
        return $this === self::PasFoto ? ['jpg', 'jpeg', 'png'] : ['pdf', 'jpg', 'jpeg', 'png'];
    }

    /**
     * Ukuran maksimal dalam kilobyte.
     */
    public function maksKb(): int
    {
        // Transkrip nilai, sertifikat, dan portofolio boleh sampai 2 MB; dokumen lain (termasuk pas foto) 1 MB.
        return in_array($this, [self::Transkrip, self::SertifikatPelatihan, self::Portofolio], true) ? 2048 : 1024;
    }

    /**
     * Definisi untuk form & halaman verifikasi di frontend.
     *
     * @return list<array<string, mixed>>
     */
    public static function definisi(): array
    {
        return array_map(fn (self $jenis) => [
            'jenis' => $jenis->value,
            'label' => $jenis->label(),
            'wajib' => $jenis->wajib(),
            'identitas' => $jenis->identitas(),
            'accept' => implode(',', array_map(fn (string $ext) => ".{$ext}", $jenis->mimes())),
            'maks_mb' => $jenis->maksKb() / 1024,
        ], self::cases());
    }
}
