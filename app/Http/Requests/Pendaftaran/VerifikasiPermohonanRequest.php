<?php

namespace App\Http\Requests\Pendaftaran;

use App\Enums\JenisDokumen;
use App\Enums\StatusPermohonan;
use App\Models\PermohonanSertifikasi;
use App\Support\PermohonanPresenter;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class VerifikasiPermohonanRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'keputusan' => ['required', Rule::in([StatusPermohonan::Diterima->value, StatusPermohonan::Ditolak->value])],
            'catatan' => ['nullable', 'required_if:keputusan,ditolak', 'string', 'max:2000'],
            'cek_dokumen' => ['nullable', 'array'],
        ];

        foreach (JenisDokumen::cases() as $jenis) {
            $rules["cek_dokumen.{$jenis->value}"] = ['nullable', 'boolean'];
        }

        return $rules;
    }

    /**
     * Permohonan hanya bisa diterima bila semua dokumen wajib ada dan dinyatakan memenuhi.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                /** @var PermohonanSertifikasi $permohonan */
                $permohonan = $this->route('permohonan');

                if ($permohonan->sudahDiputuskan()) {
                    $validator->errors()->add('keputusan', 'Permohonan ini sudah diverifikasi sebelumnya.');

                    return;
                }

                if ($this->input('keputusan') !== StatusPermohonan::Diterima->value) {
                    return;
                }

                $belumMemenuhi = collect(PermohonanPresenter::dokumen($permohonan))
                    ->filter(fn (array $dokumen) => $dokumen['wajib'])
                    ->reject(fn (array $dokumen) => $dokumen['ada'] && $this->boolean("cek_dokumen.{$dokumen['jenis']}"))
                    ->pluck('label');

                if ($belumMemenuhi->isNotEmpty()) {
                    $validator->errors()->add(
                        'keputusan',
                        'Permohonan belum bisa diterima. Dokumen wajib belum memenuhi: '.$belumMemenuhi->implode(', ').'.',
                    );
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'keputusan.required' => 'Pilih keputusan verifikasi: diterima atau ditolak.',
            'catatan.required_if' => 'Tuliskan catatan alasan penolakan agar asesi tahu apa yang perlu diperbaiki.',
        ];
    }
}
