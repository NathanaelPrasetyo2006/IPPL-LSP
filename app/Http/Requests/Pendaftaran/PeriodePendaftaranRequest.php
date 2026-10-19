<?php

namespace App\Http\Requests\Pendaftaran;

use App\Models\SkemaSertifikasi;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'lokasi' => ['required', 'array', 'min:1'],
            'lokasi.*.id_tuk' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('tuk', 'id_tuk')->whereNull('deleted_at'),
            ],
            // Lokasi tanpa skema tidak boleh disimpan, dan skemanya harus dari skema yang dibuka di atas.
            'lokasi.*.skema' => ['required', 'array', 'min:1'],
            // Tanpa distinct: wildcard bersarang akan membandingkan antar lokasi, padahal satu skema boleh di banyak lokasi.
            'lokasi.*.skema.*' => ['integer', Rule::in($this->skemaDipilih())],
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
            'lokasi' => 'lokasi TUK',
            'lokasi.*.id_tuk' => 'lokasi TUK',
            'lokasi.*.skema' => 'skema lokasi',
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
            'lokasi.required' => 'Tambahkan minimal satu lokasi TUK untuk periode ini.',
            'lokasi.min' => 'Tambahkan minimal satu lokasi TUK untuk periode ini.',
            'lokasi.*.id_tuk.required' => 'Pilih lokasi TUK.',
            'lokasi.*.id_tuk.distinct' => 'Lokasi TUK ini sudah dipilih di baris lain.',
            'lokasi.*.id_tuk.exists' => 'Lokasi TUK tidak ditemukan atau sudah dihapus.',
            'lokasi.*.skema.required' => 'Pilih minimal satu skema untuk lokasi ini.',
            'lokasi.*.skema.min' => 'Pilih minimal satu skema untuk lokasi ini.',
            'lokasi.*.skema.*.in' => 'Skema lokasi harus termasuk skema yang dibuka pada periode ini.',
        ];
    }

    /**
     * Setiap skema yang dibuka wajib diujikan di minimal satu lokasi.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                // Pesan per baris lokasi lebih jelas, jadi cek ini menunggu data lokasi lainnya valid.
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $adaLokasi = collect($this->input('lokasi'))->pluck('skema')->flatten()->map(fn ($id) => (int) $id)->all();
                $tanpaLokasi = array_diff($this->skemaDipilih(), $adaLokasi);

                if ($tanpaLokasi === []) {
                    return;
                }

                $judul = SkemaSertifikasi::query()->whereKey($tanpaLokasi)->orderBy('nomor')->pluck('judul')->join(', ');

                $validator->errors()->add('lokasi', "Setiap skema wajib memiliki minimal satu lokasi. Belum ada lokasi untuk: {$judul}.");
            },
        ];
    }

    /**
     * @return list<int>
     */
    private function skemaDipilih(): array
    {
        $skema = $this->input('skema');

        return is_array($skema) ? array_values(array_filter(array_map('intval', $skema))) : [];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nama' => is_string($this->nama) ? trim($this->nama) : $this->nama,
            'aktif' => $this->boolean('aktif'),
        ]);
    }
}
