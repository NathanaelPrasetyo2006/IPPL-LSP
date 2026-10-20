<?php

namespace App\Http\Requests\Master;

use App\Models\Tuk;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TukRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Tuk|null $tuk */
        $tuk = $this->route('tuk');

        return [
            'nama' => [
                'required',
                'string',
                'max:255',
                // Nama menjadi pembeda antar lokasi; nama milik TUK yang sudah dihapus boleh dipakai lagi.
                Rule::unique('tuk', 'nama')->whereNull('deleted_at')->ignore($tuk?->id_tuk, 'id_tuk'),
            ],
            'alamat' => ['required', 'string', 'max:1000'],
            'provinsi' => ['required', 'string', 'max:100'],
            'kota' => ['required', 'string', 'max:100'],
            'kecamatan' => ['required', 'string', 'max:100'],
            'kelurahan' => ['required', 'string', 'max:100'],
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
            'nama' => 'nama lokasi',
            'alamat' => 'detail alamat',
            'provinsi' => 'provinsi',
            'kota' => 'kota/kabupaten',
            'kecamatan' => 'kecamatan',
            'kelurahan' => 'kelurahan/desa',
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
            'nama.unique' => 'Lokasi TUK :input sudah terdaftar.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        foreach (['nama', ...Tuk::KOLOM_ALAMAT] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => trim($this->input($field))]);
            }
        }
    }
}
