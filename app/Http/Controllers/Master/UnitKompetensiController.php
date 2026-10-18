<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\UnitKompetensiRequest;
use App\Models\SkemaSertifikasi;
use App\Models\UnitKompetensi;
use Illuminate\Http\RedirectResponse;

/**
 * Unit kompetensi dikelola dari popup detail skema, jadi setiap aksi kembali ke halaman asal.
 */
class UnitKompetensiController extends Controller
{
    /**
     * Tambah unit kompetensi ke sebuah skema.
     */
    public function store(UnitKompetensiRequest $request, SkemaSertifikasi $skema): RedirectResponse
    {
        $skema->unitKompetensi()->create($request->validated());

        return back()->with('success', "Unit kompetensi berhasil ditambahkan ke skema {$skema->nomor}.");
    }

    /**
     * Perbarui unit kompetensi.
     */
    public function update(UnitKompetensiRequest $request, UnitKompetensi $unit): RedirectResponse
    {
        $unit->update($request->validated());

        return back()->with('success', 'Unit kompetensi berhasil diperbarui.');
    }

    /**
     * Hapus unit kompetensi (soft delete).
     */
    public function destroy(UnitKompetensi $unit): RedirectResponse
    {
        $unit->delete();

        return back()->with('success', "Unit kompetensi {$unit->kode} berhasil dihapus.");
    }
}
