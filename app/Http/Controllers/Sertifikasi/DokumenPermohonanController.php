<?php

namespace App\Http\Controllers\Sertifikasi;

use App\Enums\JenisDokumen;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\PermohonanSertifikasi;
use App\Support\PenyimpananDokumen;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Buka dokumen APL-01 dari disk privat. Hanya admin LSP dan pemilik permohonan yang boleh.
 */
class DokumenPermohonanController extends Controller
{
    public function __invoke(Request $request, PermohonanSertifikasi $permohonan, string $jenis): StreamedResponse
    {
        $user = $request->user();

        abort_unless(
            $user->role === UserRole::Admin || $permohonan->dataDiri?->id_user === $user->id_user,
            403,
        );

        $jenis = JenisDokumen::tryFrom($jenis) ?? abort(404);

        $path = $jenis->identitas()
            ? $permohonan->dataDiri->{$jenis->kolomDataDiri()}
            : $permohonan->dokumen()->where('jenis', $jenis->value)->value('path');

        abort_unless($path, 404);

        $ext = pathinfo($path, PATHINFO_EXTENSION);

        return PenyimpananDokumen::tampilkan($path, "{$permohonan->nomor}_{$jenis->value}.{$ext}");
    }
}
