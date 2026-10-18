<?php

namespace App\Http\Requests\Master;

use App\Models\Asesor;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AsesorRequest extends FormRequest
{
    /**
     * Password sementara tidak lagi diisi admin: sistem membuatnya dan mengirim
     * lewat email setelah asesor memverifikasi akunnya.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Asesor|null $asesor */
        $asesor = $this->route('asesor');

        return [
            'nama' => ['required', 'string', 'max:255'],
            'no_reg' => [
                'required',
                'string',
                'max:100',
                Rule::unique('asesor', 'no_reg')->ignore($asesor?->id_asesor, 'id_asesor'),
            ],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($asesor?->id_user, 'id_user'),
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
            'nama' => 'nama asesor',
            'no_reg' => 'no. registrasi/MET',
            'email' => 'email',
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
            'no_reg.unique' => 'No. registrasi :input sudah dipakai asesor lain.',
            'email.unique' => 'Email :input sudah terdaftar sebagai akun lain.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'nama' => is_string($this->nama) ? trim($this->nama) : $this->nama,
            'no_reg' => is_string($this->no_reg) ? trim($this->no_reg) : $this->no_reg,
            'email' => is_string($this->email) ? mb_strtolower(trim($this->email)) : $this->email,
        ]);
    }
}
