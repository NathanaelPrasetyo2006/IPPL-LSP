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
        foreach (['nama', 'alamat'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => trim($this->input($field))]);
            }
        }
    }
}
