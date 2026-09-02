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
            'kode' => [
                'required',
                'string',
                'max:50',
                Rule::unique('tuk', 'kode')->ignore($tuk?->id_tuk, 'id_tuk'),
            ],
            'nama' => ['required', 'string', 'max:255'],
            'jenis' => ['required', Rule::in(array_keys(Tuk::JENIS))],
            'alamat' => ['required', 'string', 'max:255'],
            'kota' => ['required', 'string', 'max:100'],
            'penanggung_jawab' => ['nullable', 'string', 'max:255'],
            'telepon' => ['nullable', 'string', 'max:30'],
            'status' => ['required', Rule::in(array_keys(Tuk::STATUS))],
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
            'kode' => 'kode TUK',
            'nama' => 'nama TUK',
            'jenis' => 'jenis TUK',
            'penanggung_jawab' => 'penanggung jawab',
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
            'kode.unique' => 'Kode TUK :input sudah terdaftar.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        foreach (['kode', 'nama', 'alamat', 'kota', 'penanggung_jawab', 'telepon'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => trim($this->input($field))]);
            }
        }
    }
}
