<?php

namespace App\Support;

use App\Enums\JenisDokumen;
use App\Models\DataDiri;
use App\Models\PermohonanSertifikasi;

/**
 * Bentuk data permohonan APL-01 untuk halaman asesi maupun verifikator.
 */
class PermohonanPresenter
{
    /**
     * Ringkasan untuk tabel/daftar.
     *
     * @return array<string, mixed>
     */
    public static function ringkas(PermohonanSertifikasi $p): array
    {
        return [
            'id_permohonan' => $p->id_permohonan,
            'nomor' => $p->nomor,
            'status' => $p->status->value,
            'status_label' => $p->status->label(),
            'skema' => $p->skema?->only(['id_skema', 'judul', 'nomor']),
            'periode' => $p->periode?->only(['id_periode', 'nama']),
            'nama_asesi' => $p->dataDiri?->nama_lengkap,
            'kode_asesi' => $p->dataDiri?->kode_asesi,
            'diajukan_pada' => $p->created_at?->locale('id')->translatedFormat('d M Y H:i'),
            'diverifikasi_pada' => $p->diverifikasi_pada?->locale('id')->translatedFormat('d M Y H:i'),
        ];
    }

    /**
     * Detail lengkap: data pemohon, data sertifikasi, dan daftar dokumen.
     *
     * @return array<string, mixed>
     */
    public static function detail(PermohonanSertifikasi $p): array
    {
        $p->loadMissing(['dataDiri.negara', 'dataDiri.user:id_user,email', 'skema.unitKompetensi', 'periode', 'dokumen', 'verifikator']);

        $d = $p->dataDiri;

        return [
            ...self::ringkas($p),
            'tujuan_asesmen' => $p->tujuan_asesmen->label().($p->tujuan_asesmen_lainnya ? ": {$p->tujuan_asesmen_lainnya}" : ''),
            'catatan_verifikasi' => $p->catatan_verifikasi,
            'cek_dokumen' => (object) ($p->cek_dokumen ?? []),
            'verifikator' => $p->verifikator?->email,
            'skema' => [
                ...$p->skema->only(['id_skema', 'judul', 'nomor']),
                'unit_kompetensi' => $p->skema->unitKompetensi
                    ->sortBy([['urutan', 'asc'], ['kode', 'asc']])
                    ->map->only(['id_unit_kompetensi', 'kode', 'judul'])
                    ->values(),
            ],
            'pemohon' => [
                'kode_asesi' => $d->kode_asesi,
                'nama_lengkap' => $d->nama_lengkap,
                'nik' => $d->nik,
                'tempat_tgl_lahir' => trim(($d->tempat_lahir ?? '-').', '.($d->tgl_lahir?->locale('id')->translatedFormat('d F Y') ?? '-')),
                'jenis_kelamin' => DataDiri::JENIS_KELAMIN[$d->jenis_kelamin] ?? '-',
                'kebangsaan' => $d->negara?->nama_negara,
                'alamat_ktp' => $d->alamat_ktp,
                'alamat_domisili' => collect([$d->alamat_domisili, $d->kelurahan, $d->kecamatan, $d->kota, $d->provinsi, $d->kode_pos])->filter()->implode(', '),
                'no_telp' => $d->no_telp,
                'email' => $d->user?->email,
                'pendidikan' => trim(($d->jenjang_pendidikan ?? '').' – '.($d->nama_sekolah ?? ''), ' –'),
                'pekerjaan' => $d->pekerjaan,
                'nama_institusi' => $d->nama_institusi,
                'jabatan' => $d->jabatan,
                'alamat_kantor' => trim(($d->alamat_kantor ?? '').' '.($d->kode_pos_kantor ?? '')) ?: null,
                'telp_kantor' => $d->telp_kantor,
                'email_kantor' => $d->email_kantor,
            ],
            'dokumen' => self::dokumen($p),
        ];
    }

    /**
     * Semua jenis dokumen APL-01 beserta file yang tersedia untuk permohonan ini.
     *
     * @return list<array<string, mixed>>
     */
    public static function dokumen(PermohonanSertifikasi $p): array
    {
        $unggahan = $p->dokumen->keyBy(fn ($dokumen) => $dokumen->jenis->value);

        return array_map(function (JenisDokumen $jenis) use ($p, $unggahan) {
            $path = $jenis->identitas()
                ? $p->dataDiri->{$jenis->kolomDataDiri()}
                : $unggahan->get($jenis->value)?->path;

            return [
                'jenis' => $jenis->value,
                'label' => $jenis->label(),
                'wajib' => $jenis->wajib(),
                'identitas' => $jenis->identitas(),
                'ada' => filled($path),
                'nama_file' => $path ? ($unggahan->get($jenis->value)?->nama_asli ?? basename($path)) : null,
                'ukuran' => $unggahan->get($jenis->value)?->ukuran,
                'url' => $path ? route('dokumen.permohonan', [$p->id_permohonan, $jenis->value]) : null,
            ];
        }, JenisDokumen::cases());
    }
}
