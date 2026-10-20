<?php

namespace App\Http\Requests\Sertifikasi;

use App\Enums\JenisDokumen;
use App\Enums\TujuanAsesmen;
use App\Models\DataDiri;
use App\Models\Negara;
use App\Models\PeriodePendaftaran;
use App\Models\PermohonanSertifikasi;
use App\Models\SkemaSertifikasi;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi formulir FR-APL-01: data pribadi, data pekerjaan, data sertifikasi, dan dokumen.
 */
class PermohonanApl01Request extends FormRequest
{
    /**
     * Kolom profil yang disalin ke data_diri.
     *
     * @var list<string>
     */
    public const KOLOM_DATA_DIRI = [
        'nama_lengkap',
        'nik',
        'tempat_lahir',
        'tgl_lahir',
        'jenis_kelamin',
        'id_negara',
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
        'no_telp',
        'jenjang_pendidikan',
        'nama_sekolah',
        'pekerjaan',
        'nama_institusi',
        'jabatan',
        'alamat_kantor',
        'provinsi_kantor',
        'kota_kantor',
        'kecamatan_kantor',
        'kelurahan_kantor',
        'telp_kantor',
        'email_kantor',
    ];

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var DataDiri|null $dataDiri */
        $dataDiri = $this->user()?->dataDiri;

        // Mode perbaiki: periode, skema, dan dokumen yang sudah ada diambil dari permohonan itu sendiri.
        /** @var PermohonanSertifikasi|null $permohonan */
        $permohonan = $this->route('permohonan');

        /** @var PeriodePendaftaran $periode */
        $periode = $permohonan?->periode ?? $this->route('periode');

        /** @var SkemaSertifikasi $skema */
        $skema = $permohonan?->skema ?? $this->route('skema');

        $dokumenPermohonan = $permohonan?->dokumen->pluck('jenis')->map->value->all() ?? [];

        $rules = [
            // Bagian 1a: data pribadi.
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'nik' => ['required', 'digits:16', Rule::unique('data_diri', 'nik')->ignore($dataDiri?->id_data_diri, 'id_data_diri')],
            'tempat_lahir' => ['required', 'string', 'max:100'],
            'tgl_lahir' => ['required', 'date', 'before:-15 years'],
            'jenis_kelamin' => ['required', Rule::in(array_keys(DataDiri::JENIS_KELAMIN))],
            // WNI otomatis Indonesia (diisi di prepareForValidation); WNA memilih negara asal selain Indonesia.
            'kebangsaan' => ['required', Rule::in(['wni', 'wna'])],
            'id_negara' => [
                'required',
                'integer',
                Rule::exists('negara', 'id_negara'),
                ...($this->input('kebangsaan') === 'wna' ? [Rule::notIn([self::idIndonesia()])] : []),
            ],
            'alamat_ktp' => ['required', 'string', 'max:500'],
            'provinsi_ktp' => ['required', 'string', 'max:100'],
            'kota_ktp' => ['required', 'string', 'max:100'],
            'kecamatan_ktp' => ['required', 'string', 'max:100'],
            'kelurahan_ktp' => ['required', 'string', 'max:100'],
            'alamat_domisili' => ['required', 'string', 'max:500'],
            'provinsi' => ['required', 'string', 'max:100'],
            'kota' => ['required', 'string', 'max:100'],
            'kecamatan' => ['required', 'string', 'max:100'],
            'kelurahan' => ['required', 'string', 'max:100'],
            'no_telp' => ['required', 'string', 'regex:/^\+?[0-9]{9,15}$/'],
            'jenjang_pendidikan' => ['required', Rule::in(array_keys(DataDiri::PENDIDIKAN))],
            'nama_sekolah' => ['required', 'string', 'max:255'],
            'pekerjaan' => ['required', 'string', 'max:255'],

            // Bagian 1b: data pekerjaan sekarang (opsional).
            'nama_institusi' => ['nullable', 'string', 'max:255'],
            'jabatan' => ['nullable', 'string', 'max:255'],
            'alamat_kantor' => ['nullable', 'string', 'max:500'],
            // Wilayah kantor wajib dipilih bila detail alamat kantor diisi.
            'provinsi_kantor' => ['nullable', 'required_with:alamat_kantor', 'string', 'max:100'],
            'kota_kantor' => ['nullable', 'required_with:alamat_kantor', 'string', 'max:100'],
            'kecamatan_kantor' => ['nullable', 'required_with:alamat_kantor', 'string', 'max:100'],
            'kelurahan_kantor' => ['nullable', 'required_with:alamat_kantor', 'string', 'max:100'],
            'telp_kantor' => ['nullable', 'string', 'regex:/^\+?[0-9]{6,15}$/'],
            'email_kantor' => ['nullable', 'email', 'max:255'],

            // Bagian 2: data sertifikasi.
            'tujuan_asesmen' => ['required', Rule::enum(TujuanAsesmen::class)],
            'tujuan_asesmen_lainnya' => ['nullable', 'required_if:tujuan_asesmen,lainnya', 'string', 'max:255'],
            'id_tuk' => ['required', 'integer', Rule::in($periode->lokasiSkema($skema->id_skema)->pluck('id_tuk')->all())],

            'pernyataan' => ['accepted'],
            'dokumen' => ['nullable', 'array'],
        ];

        // Bagian 3: dokumen. Dokumen yang sudah tersimpan (identitas di profil, atau dokumen permohonan
        // yang sedang diperbaiki) boleh tidak diunggah ulang.
        foreach (JenisDokumen::cases() as $jenis) {
            $sudahAda = $jenis->identitas()
                ? filled($dataDiri?->{$jenis->kolomDataDiri()})
                : in_array($jenis->value, $dokumenPermohonan, true);

            $rules["dokumen.{$jenis->value}"] = [
                $jenis->wajib() && ! $sudahAda ? 'required' : 'nullable',
                'file',
                'mimes:'.implode(',', $jenis->mimes()),
                'max:'.$jenis->maksKb(),
            ];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $attributes = [
            'nama_lengkap' => 'nama lengkap',
            'nik' => 'NIK',
            'tempat_lahir' => 'tempat lahir',
            'tgl_lahir' => 'tanggal lahir',
            'jenis_kelamin' => 'jenis kelamin',
            'kebangsaan' => 'kebangsaan',
            'id_negara' => 'negara asal',
            'alamat_ktp' => 'alamat sesuai KTP',
            'provinsi_ktp' => 'provinsi KTP',
            'kota_ktp' => 'kota/kabupaten KTP',
            'kecamatan_ktp' => 'kecamatan KTP',
            'kelurahan_ktp' => 'kelurahan/desa KTP',
            'alamat_domisili' => 'alamat domisili',
            'no_telp' => 'no. HP',
            'jenjang_pendidikan' => 'pendidikan terakhir',
            'nama_sekolah' => 'nama sekolah/perguruan tinggi',
            'nama_institusi' => 'nama institusi/perusahaan',
            'alamat_kantor' => 'alamat kantor',
            'provinsi_kantor' => 'provinsi kantor',
            'kota_kantor' => 'kota/kabupaten kantor',
            'kecamatan_kantor' => 'kecamatan kantor',
            'kelurahan_kantor' => 'kelurahan/desa kantor',
            'telp_kantor' => 'telepon kantor',
            'email_kantor' => 'email kantor',
            'tujuan_asesmen' => 'tujuan asesmen',
            'tujuan_asesmen_lainnya' => 'keterangan tujuan asesmen',
            'id_tuk' => 'lokasi uji',
        ];

        foreach (JenisDokumen::cases() as $jenis) {
            $attributes["dokumen.{$jenis->value}"] = $jenis->label();
        }

        return $attributes;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nik.digits' => 'NIK harus terdiri dari 16 digit angka.',
            'id_negara.not_in' => 'Untuk WNA, pilih negara asal selain Indonesia.',
            'nik.unique' => 'NIK :input sudah terdaftar pada akun lain. Hubungi admin LSP bila ini NIK Anda.',
            'tgl_lahir.before' => 'Usia pemohon minimal 15 tahun.',
            'no_telp.regex' => 'No. HP hanya boleh berisi angka (boleh diawali +), 9–15 digit.',
            'telp_kantor.regex' => 'Telepon kantor hanya boleh berisi angka (boleh diawali +).',
            'tujuan_asesmen_lainnya.required_if' => 'Jelaskan tujuan asesmen lainnya.',
            'id_tuk.required' => 'Pilih lokasi uji.',
            'id_tuk.in' => 'Lokasi uji yang dipilih tidak tersedia untuk skema ini.',
            'pernyataan.accepted' => 'Anda harus menyatakan bahwa data yang diisi benar.',
            'dokumen.*.required' => ':attribute wajib diunggah.',
            'dokumen.*.mimes' => ':attribute harus berformat :values.',
            'dokumen.*.max' => 'Ukuran :attribute maksimal :max KB.',
        ];
    }

    public static function idIndonesia(): ?int
    {
        return Negara::query()->where('nama_negara', 'Indonesia')->value('id_negara');
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('kebangsaan') === 'wni') {
            $this->merge(['id_negara' => self::idIndonesia()]);
        }

        $bersih = fn (?string $nilai) => is_string($nilai) ? preg_replace('/[\s\-()]/', '', $nilai) : $nilai;

        $this->merge([
            'nama_lengkap' => is_string($this->nama_lengkap) ? trim($this->nama_lengkap) : $this->nama_lengkap,
            'no_telp' => $bersih($this->no_telp),
            'telp_kantor' => $bersih($this->telp_kantor),
            'email_kantor' => is_string($this->email_kantor) ? mb_strtolower(trim($this->email_kantor)) : $this->email_kantor,
        ]);
    }
}
