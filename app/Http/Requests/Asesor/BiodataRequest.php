<?php

namespace App\Http\Requests\Asesor;

use App\Models\Asesor;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BiodataRequest extends FormRequest
{
    /**
     * Batas jumlah karakter isian teks bebas. Dihitung per karakter (bukan per kata)
     * supaya pasti muat di kolom database dan di Kartu Tanda Asesor.
     *
     * @var array<string, int>
     */
    public const BATAS_KARAKTER = [
        'tempat_lahir' => 50,
        'bidang_keahlian' => 50,
        'instansi' => 100,
        'alamat' => 200,
    ];

    /**
     * Nama dan no. registrasi tidak ada di sini: keduanya mengikuti sertifikat
     * dan hanya boleh diubah admin LSP.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Asesor|null $asesor */
        $asesor = $this->user()?->asesor;

        return [
            'nik' => [
                'required',
                'digits:16',
                Rule::unique('asesor', 'nik')->ignore($asesor?->id_asesor, 'id_asesor'),
            ],
            'tempat_lahir' => ['required', 'string', 'max:'.self::BATAS_KARAKTER['tempat_lahir']],
            'tgl_lahir' => ['required', 'date', 'before:-17 years'],
            'jenis_kelamin' => ['required', Rule::in(array_keys(Asesor::JENIS_KELAMIN))],
            'no_telp' => ['required', 'string', 'regex:/^\+?[0-9]{9,15}$/'],
            'alamat' => ['required', 'string', 'max:'.self::BATAS_KARAKTER['alamat']],
            'provinsi' => ['required', 'string', 'max:100'],
            'kota' => ['required', 'string', 'max:100'],
            'kecamatan' => ['required', 'string', 'max:100'],
            'kelurahan' => ['required', 'string', 'max:100'],
            'pendidikan_terakhir' => ['required', Rule::in(array_keys(Asesor::PENDIDIKAN))],
            'instansi' => ['nullable', 'string', 'max:'.self::BATAS_KARAKTER['instansi']],
            'bidang_keahlian' => ['required', 'string', 'max:'.self::BATAS_KARAKTER['bidang_keahlian']],
            'masa_berlaku_sertifikat' => ['required', 'date'],
            // Pas foto wajib saat pertama kali; setelahnya boleh dikosongkan untuk memakai foto lama.
            'pas_foto' => [
                $asesor?->file_pas_foto ? 'nullable' : 'required',
                'image',
                'mimes:jpg,jpeg,png',
                'max:2048',
            ],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nik' => 'NIK',
            'tempat_lahir' => 'tempat lahir',
            'tgl_lahir' => 'tanggal lahir',
            'jenis_kelamin' => 'jenis kelamin',
            'no_telp' => 'no. telepon',
            'alamat' => 'detail alamat',
            'provinsi' => 'provinsi',
            'kota' => 'kota/kabupaten',
            'kecamatan' => 'kecamatan',
            'kelurahan' => 'kelurahan/desa',
            'pendidikan_terakhir' => 'pendidikan terakhir',
            'instansi' => 'instansi',
            'bidang_keahlian' => 'bidang keahlian',
            'masa_berlaku_sertifikat' => 'masa berlaku sertifikat',
            'pas_foto' => 'pas foto',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nik.digits' => 'NIK harus terdiri dari 16 digit angka.',
            'nik.unique' => 'NIK :input sudah terdaftar pada asesor lain.',
            'tgl_lahir.before' => 'Usia asesor minimal 17 tahun.',
            'no_telp.regex' => 'No. telepon hanya boleh berisi angka (boleh diawali +), 9–15 digit.',
            'pas_foto.max' => 'Ukuran pas foto maksimal 2 MB.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $trim = fn (string $key) => is_string($this->input($key)) ? trim($this->input($key)) : $this->input($key);

        $this->merge([
            'nik' => $trim('nik'),
            'tempat_lahir' => $trim('tempat_lahir'),
            // Spasi dan tanda hubung pada nomor telepon dibuang supaya formatnya seragam.
            'no_telp' => is_string($this->no_telp) ? preg_replace('/[\s\-()]/', '', $this->no_telp) : $this->no_telp,
            'alamat' => $trim('alamat'),
            'provinsi' => $trim('provinsi'),
            'kota' => $trim('kota'),
            'kecamatan' => $trim('kecamatan'),
            'kelurahan' => $trim('kelurahan'),
            'instansi' => $trim('instansi'),
            'bidang_keahlian' => $trim('bidang_keahlian'),
        ]);
    }
}
