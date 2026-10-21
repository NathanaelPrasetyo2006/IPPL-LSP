<?php

namespace App\Http\Requests\Master;

use App\Models\SkemaSertifikasi;
use App\Models\UnitKompetensi;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UnitKompetensiRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var UnitKompetensi|null $unit */
        $unit = $this->route('unit');

        /** @var SkemaSertifikasi|null $skema */
        $skema = $this->route('skema');

        return [
            // Kode unit cukup unik di dalam satu skema; unit yang sudah dihapus tidak dihitung.
            'kode' => [
                'required',
                'string',
                'max:100',
                'not_regex:/\s/u',
                Rule::unique('unit_kompetensi', 'kode')
                    ->where('id_skema', $unit?->id_skema ?? $skema?->id_skema)
                    ->whereNull('deleted_at')
                    ->ignore($unit?->id_unit_kompetensi, 'id_unit_kompetensi'),
            ],
            'judul' => ['required', 'string', 'max:255'],
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
            'kode' => 'kode unit',
            'judul' => 'judul unit',
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
            'kode.not_regex' => 'Kode unit tidak boleh mengandung spasi.',
            'kode.unique' => 'Kode unit :input sudah ada di skema ini.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            // Huruf besar semua (seperti No. MET) supaya keunikan kode tidak lolos hanya karena beda kapitalisasi.
            'kode' => is_string($this->kode) ? mb_strtoupper(trim($this->kode)) : $this->kode,
            'judul' => is_string($this->judul) ? trim($this->judul) : $this->judul,
        ]);
    }
}
