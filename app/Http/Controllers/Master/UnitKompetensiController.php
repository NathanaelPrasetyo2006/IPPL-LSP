<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\UnitKompetensiRequest;
use App\Models\SkemaSertifikasi;
use App\Models\UnitKompetensi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Unit kompetensi dikelola dari popup detail skema, jadi setiap aksi kembali ke halaman asal.
 */
class UnitKompetensiController extends Controller
{
    /**
     * Tambah unit kompetensi ke urutan paling akhir sebuah skema.
     */
    public function store(UnitKompetensiRequest $request, SkemaSertifikasi $skema): RedirectResponse
    {
        $skema->unitKompetensi()->create([
            ...$request->validated(),
            'urutan' => (int) $skema->unitKompetensi()->max('urutan') + 1,
        ]);

        return back()->with('success', "Unit kompetensi berhasil ditambahkan ke skema {$skema->nomor}.");
    }

    /**
     * Perbarui kode dan judul unit kompetensi. Urutan diatur lewat susun().
     */
    public function update(UnitKompetensiRequest $request, UnitKompetensi $unit): RedirectResponse
    {
        $unit->update($request->validated());

        return back()->with('success', 'Unit kompetensi berhasil diperbarui.');
    }

    /**
     * Simpan hasil drag and drop sekaligus unit yang dihapus dari popup detail skema.
     * Unit yang tersisa dinomori ulang 1..n sesuai urutan kiriman.
     */
    public function susun(Request $request, SkemaSertifikasi $skema): RedirectResponse
    {
        $data = $request->validate([
            'urutan' => ['present', 'array'],
            'urutan.*' => ['integer', 'distinct'],
            'hapus' => ['present', 'array'],
            'hapus.*' => ['integer', 'distinct'],
        ]);

        $urutan = array_map('intval', $data['urutan']);
        $hapus = array_map('intval', $data['hapus']);
        $semua = $skema->unitKompetensi()->pluck('id_unit_kompetensi')->map(fn ($id) => (int) $id)->sort()->values()->all();

        // Kiriman harus mencakup semua unit skema tepat satu kali; bila tidak, berarti data di layar sudah usang.
        $dikirim = [...$urutan, ...$hapus];
        sort($dikirim);

        if ($dikirim !== $semua) {
            return back()->with('error', 'Daftar unit kompetensi sudah berubah. Muat ulang halaman lalu susun kembali.');
        }

        DB::transaction(function () use ($skema, $urutan, $hapus) {
            $skema->unitKompetensi()->whereKey($hapus)->get()->each->delete();

            foreach ($urutan as $index => $id) {
                $skema->unitKompetensi()->whereKey($id)->update(['urutan' => $index + 1]);
            }
        });

        $pesan = count($hapus) > 0
            ? count($hapus).' unit kompetensi dihapus dan urutan diperbarui.'
            : 'Urutan unit kompetensi berhasil disimpan.';

        return back()->with('success', $pesan);
    }
}
