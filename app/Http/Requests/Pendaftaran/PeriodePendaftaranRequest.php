<?php

namespace App\Http\Requests\Pendaftaran;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PeriodePendaftaranRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'tanggal_buka' => ['required', 'date'],
            'tanggal_tutup' => ['required', 'date', 'after_or_equal:tanggal_buka'],
            'kuota' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
            'aktif' => ['boolean'],
            'skema' => ['required', 'array', 'min:1'],
            'skema.*' => [
                'integer',
                'distinct',
                Rule::exists('skema_sertifikasi', 'id_skema')->whereNull('deleted_at'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nama' => 'nama periode',
            'tanggal_buka' => 'tanggal buka',
            'tanggal_tutup' => 'tanggal tutup',
            'kuota' => 'kuota',
            'keterangan' => 'keterangan',
            'skema' => 'skema sertifikasi',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tanggal_tutup.after_or_equal' => 'Tanggal tutup tidak boleh sebelum tanggal buka.',
            'skema.required' => 'Pilih minimal satu skema sertifikasi yang dibuka pada periode ini.',
            'skema.min' => 'Pilih minimal satu skema sertifikasi yang dibuka pada periode ini.',
            'skema.*.exists' => 'Skema yang dipilih tidak ditemukan atau sudah dihapus.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nama' => is_string($this->nama) ? trim($this->nama) : $this->nama,
            'aktif' => $this->boolean('aktif'),
        ]);
    }
}
