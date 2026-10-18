<?php

namespace App\Http\Requests\Sertifikasi;

use App\Enums\JenisDokumen;
use App\Enums\TujuanAsesmen;
use App\Models\DataDiri;
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
        'alamat_domisili',
        'provinsi',
        'kota',
        'kecamatan',
        'kelurahan',
        'kode_pos',
        'no_telp',
        'jenjang_pendidikan',
        'nama_sekolah',
        'pekerjaan',
        'nama_institusi',
        'jabatan',
        'alamat_kantor',
        'kode_pos_kantor',
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

        $rules = [
            // Bagian 1a: data pribadi.
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'nik' => ['required', 'digits:16', Rule::unique('data_diri', 'nik')->ignore($dataDiri?->id_data_diri, 'id_data_diri')],
            'tempat_lahir' => ['required', 'string', 'max:100'],
            'tgl_lahir' => ['required', 'date', 'before:-15 years'],
            'jenis_kelamin' => ['required', Rule::in(array_keys(DataDiri::JENIS_KELAMIN))],
            'id_negara' => ['required', 'integer', Rule::exists('negara', 'id_negara')],
            'alamat_ktp' => ['required', 'string', 'max:500'],
            'alamat_domisili' => ['required', 'string', 'max:500'],
            'provinsi' => ['required', 'string', 'max:100'],
            'kota' => ['required', 'string', 'max:100'],
            'kecamatan' => ['required', 'string', 'max:100'],
            'kelurahan' => ['required', 'string', 'max:100'],
            'kode_pos' => ['required', 'digits:5'],
            'no_telp' => ['required', 'string', 'regex:/^\+?[0-9]{9,15}$/'],
            'jenjang_pendidikan' => ['required', Rule::in(array_keys(DataDiri::PENDIDIKAN))],
            'nama_sekolah' => ['required', 'string', 'max:255'],
            'pekerjaan' => ['required', 'string', 'max:255'],

            // Bagian 1b: data pekerjaan sekarang (opsional).
            'nama_institusi' => ['nullable', 'string', 'max:255'],
            'jabatan' => ['nullable', 'string', 'max:255'],
            'alamat_kantor' => ['nullable', 'string', 'max:500'],
            'kode_pos_kantor' => ['nullable', 'digits:5'],
            'telp_kantor' => ['nullable', 'string', 'regex:/^\+?[0-9]{6,15}$/'],
            'email_kantor' => ['nullable', 'email', 'max:255'],

            // Bagian 2: data sertifikasi.
            'tujuan_asesmen' => ['required', Rule::enum(TujuanAsesmen::class)],
            'tujuan_asesmen_lainnya' => ['nullable', 'required_if:tujuan_asesmen,lainnya', 'string', 'max:255'],

            'pernyataan' => ['accepted'],
            'dokumen' => ['nullable', 'array'],
        ];

        // Bagian 3: dokumen. Dokumen identitas yang sudah pernah diunggah boleh tidak diunggah ulang.
        foreach (JenisDokumen::cases() as $jenis) {
            $sudahAda = $jenis->identitas() && filled($dataDiri?->{$jenis->kolomDataDiri()});

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
            'id_negara' => 'kebangsaan',
            'alamat_ktp' => 'alamat sesuai KTP',
            'alamat_domisili' => 'alamat domisili',
            'kode_pos' => 'kode pos',
            'no_telp' => 'no. HP',
            'jenjang_pendidikan' => 'pendidikan terakhir',
            'nama_sekolah' => 'nama sekolah/perguruan tinggi',
            'nama_institusi' => 'nama institusi/perusahaan',
            'alamat_kantor' => 'alamat kantor',
            'kode_pos_kantor' => 'kode pos kantor',
            'telp_kantor' => 'telepon kantor',
            'email_kantor' => 'email kantor',
            'tujuan_asesmen' => 'tujuan asesmen',
            'tujuan_asesmen_lainnya' => 'keterangan tujuan asesmen',
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
            'nik.unique' => 'NIK :input sudah terdaftar pada akun lain. Hubungi admin LSP bila ini NIK Anda.',
            'tgl_lahir.before' => 'Usia pemohon minimal 15 tahun.',
            'no_telp.regex' => 'No. HP hanya boleh berisi angka (boleh diawali +), 9–15 digit.',
            'telp_kantor.regex' => 'Telepon kantor hanya boleh berisi angka (boleh diawali +).',
            'tujuan_asesmen_lainnya.required_if' => 'Jelaskan tujuan asesmen lainnya.',
            'pernyataan.accepted' => 'Anda harus menyatakan bahwa data yang diisi benar.',
            'dokumen.*.required' => ':attribute wajib diunggah.',
            'dokumen.*.mimes' => ':attribute harus berformat :values.',
            'dokumen.*.max' => 'Ukuran :attribute maksimal :max KB.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $bersih = fn (?string $nilai) => is_string($nilai) ? preg_replace('/[\s\-()]/', '', $nilai) : $nilai;

        $this->merge([
            'nama_lengkap' => is_string($this->nama_lengkap) ? trim($this->nama_lengkap) : $this->nama_lengkap,
            'no_telp' => $bersih($this->no_telp),
            'telp_kantor' => $bersih($this->telp_kantor),
            'email_kantor' => is_string($this->email_kantor) ? mb_strtolower(trim($this->email_kantor)) : $this->email_kantor,
        ]);
    }
}
