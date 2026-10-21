<?php

namespace App\Http\Requests\Master;

use App\Models\SkemaSertifikasi;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SkemaSertifikasiRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var SkemaSertifikasi|null $skema */
        $skema = $this->route('skema');

        return [
            'judul' => ['required', 'string', 'max:255'],
            'nomor' => [
                'required',
                'string',
                'max:100',
                'not_regex:/\s/u',
                // Kode milik skema yang sudah dihapus boleh dipakai lagi.
                Rule::unique('skema_sertifikasi', 'nomor')->whereNull('deleted_at')->ignore($skema?->id_skema, 'id_skema'),
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
            'judul' => 'nama skema',
            'nomor' => 'kode skema',
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
            'nomor.not_regex' => 'Kode skema tidak boleh mengandung spasi.',
            'nomor.unique' => 'Kode skema :input sudah terdaftar.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'judul' => is_string($this->judul) ? trim($this->judul) : $this->judul,
            // Huruf besar semua (seperti No. MET) supaya keunikan kode tidak lolos hanya karena beda kapitalisasi.
            'nomor' => is_string($this->nomor) ? mb_strtoupper(trim($this->nomor)) : $this->nomor,
        ]);
    }
}
