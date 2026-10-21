<?php

namespace App\Http\Controllers\Asesor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Asesor\BiodataRequest;
use App\Models\Asesor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BiodataController extends Controller
{
    /**
     * Pas foto disimpan di disk privat dan hanya disajikan lewat route ini.
     */
    private const DISK = 'local';

    /**
     * Form lengkapi biodata asesor.
     */
    public function edit(Request $request): Response
    {
        $asesor = $this->asesor($request);

        return Inertia::render('asesor/biodata', [
            'asesor' => [
                ...$this->ringkasan($asesor),
                'email' => $request->user()->email,
                'nik' => $asesor->nik,
                'tempat_lahir' => $asesor->tempat_lahir,
                'tgl_lahir' => $asesor->tgl_lahir?->toDateString(),
                'jenis_kelamin' => $asesor->jenis_kelamin,
                'no_telp' => $asesor->no_telp,
                'alamat' => $asesor->alamat,
                'provinsi' => $asesor->provinsi,
                'kota' => $asesor->kota,
                'kecamatan' => $asesor->kecamatan,
                'kelurahan' => $asesor->kelurahan,
                'pendidikan_terakhir' => $asesor->pendidikan_terakhir,
                'instansi' => $asesor->instansi,
                'bidang_keahlian' => $asesor->bidang_keahlian,
                'masa_berlaku_sertifikat' => $asesor->masa_berlaku_sertifikat?->toDateString(),
                'diperbarui_pada' => $asesor->biodata_diperbarui_pada?->translatedFormat('d M Y H:i'),
            ],
            'options' => [
                'jenis_kelamin' => Asesor::JENIS_KELAMIN,
                'pendidikan' => Asesor::PENDIDIKAN,
                'batas_karakter' => BiodataRequest::BATAS_KARAKTER,
            ],
        ]);
    }

    /**
     * Simpan biodata dan (bila diunggah) ganti pas foto.
     */
    public function update(BiodataRequest $request): RedirectResponse
    {
        $asesor = $this->asesor($request);
        $data = Arr::except($request->validated(), 'pas_foto');
        $fotoLama = null;

        if ($request->hasFile('pas_foto')) {
            $fotoLama = $asesor->file_pas_foto;
            $foto = $request->file('pas_foto');
            $data['file_pas_foto'] = $foto->storeAs('asesor/pas-foto', $this->namaPasFoto($asesor, $foto->extension()), self::DISK);
        }

        $asesor->update([...$data, 'biodata_diperbarui_pada' => now()]);

        // Foto lama baru dihapus setelah data tersimpan, supaya tidak hilang bila update gagal.
        if ($fotoLama) {
            Storage::disk(self::DISK)->delete($fotoLama);
        }

        return to_route('asesor.biodata.edit')->with('success', $asesor->biodataLengkap()
            ? 'Biodata berhasil disimpan. Kartu Tanda Asesor Anda sudah bisa diunduh.'
            : 'Biodata berhasil disimpan.');
    }

    /**
     * Tampilkan pas foto milik asesor yang sedang login.
     */
    public function foto(Request $request): StreamedResponse
    {
        $asesor = $this->asesor($request);

        abort_unless($asesor->file_pas_foto && Storage::disk(self::DISK)->exists($asesor->file_pas_foto), 404);

        return Storage::disk(self::DISK)->response($asesor->file_pas_foto, headers: [
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }

    /**
     * Pratinjau dan unduh Kartu Tanda Asesor.
     */
    public function kartu(Request $request): Response
    {
        $asesor = $this->asesor($request);

        return Inertia::render('asesor/kartu', [
            'asesor' => [
                ...$this->ringkasan($asesor),
                'bidang_keahlian' => $asesor->bidang_keahlian,
                'instansi' => $asesor->instansi,
                'masa_berlaku_sertifikat' => $asesor->masa_berlaku_sertifikat?->locale('id')->translatedFormat('d F Y'),
                'kedaluwarsa' => $asesor->sertifikatKedaluwarsa(),
            ],
            'lembaga' => config('app.name'),
            // Tanggal pada kartu selalu berbahasa Indonesia, terlepas dari APP_LOCALE.
            'diterbitkan' => now()->locale('id')->translatedFormat('d F Y'),
            'biodata_wajib_kosong' => array_values(array_filter(
                Asesor::BIODATA_WAJIB,
                fn (string $kolom) => blank($asesor->{$kolom}),
            )),
        ]);
    }

    /**
     * Nama file pas foto: "<kode asesor>_<kode unik>", mis. "ASR-2026-00001_7K2QX9AB.jpg".
     * Kode unik 8 karakter campuran huruf kapital dan angka, dibuat ulang setiap foto diganti.
     */
    private function namaPasFoto(Asesor $asesor, string $ekstensi): string
    {
        do {
            $kode = Str::upper(Str::random(8));
        } while (! preg_match('/[A-Z]/', $kode) || ! preg_match('/\d/', $kode));

        return ($asesor->kode_asesor ?? 'ASESOR')."_{$kode}.{$ekstensi}";
    }

    /**
     * Data asesor milik akun yang sedang login.
     */
    private function asesor(Request $request): Asesor
    {
        $asesor = $request->user()->asesor;

        // Akun ber-role asesor selalu dibuat bersama baris asesor; ini hanya pengaman.
        abort_if($asesor === null, 404, 'Data asesor untuk akun ini tidak ditemukan.');

        return $asesor;
    }

    /**
     * Identitas dasar yang dipakai di halaman biodata dan kartu.
     *
     * @return array<string, mixed>
     */
    private function ringkasan(Asesor $asesor): array
    {
        return [
            'kode_asesor' => $asesor->kode_asesor,
            'nama' => $asesor->nama,
            'no_reg' => $asesor->no_reg,
            'lengkap' => $asesor->biodataLengkap(),
            // Versi pada URL memaksa browser memuat ulang foto setelah diganti.
            'foto_url' => $asesor->file_pas_foto
                ? route('asesor.biodata.foto', ['v' => $asesor->biodata_diperbarui_pada?->timestamp])
                : null,
        ];
    }
}
