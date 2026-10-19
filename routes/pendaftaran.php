<?php

use App\Http\Controllers\NotifikasiController;
use App\Http\Controllers\Pendaftaran\PeriodePendaftaranController;
use App\Http\Controllers\Pendaftaran\VerifikasiPermohonanController;
use App\Http\Controllers\Sertifikasi\DokumenPermohonanController;
use App\Http\Controllers\Sertifikasi\PendaftaranSertifikasiController;
use App\Http\Controllers\WilayahController;
use Illuminate\Support\Facades\Route;

// Admin LSP: atur periode pendaftaran dan verifikasi berkas APL-01.
Route::middleware(['auth', 'role:admin'])
    ->prefix('pendaftaran')
    ->name('pendaftaran.')
    ->group(function () {
        Route::patch('periode/{periode}/status', [PeriodePendaftaranController::class, 'toggle'])->name('periode.toggle');
        Route::resource('periode', PeriodePendaftaranController::class);

        Route::get('verifikasi', [VerifikasiPermohonanController::class, 'index'])->name('verifikasi.index');
        Route::get('verifikasi/{permohonan}', [VerifikasiPermohonanController::class, 'show'])->name('verifikasi.show');
        Route::put('verifikasi/{permohonan}', [VerifikasiPermohonanController::class, 'update'])->name('verifikasi.update');
    });

// Calon asesi: wajib sudah memverifikasi email sebelum mendaftar sertifikasi.
Route::middleware(['auth', 'role:asesi', 'verified'])
    ->prefix('sertifikasi')
    ->name('sertifikasi.')
    ->group(function () {
        Route::get('/', [PendaftaranSertifikasiController::class, 'index'])->name('index');
        Route::get('daftar/{periode}/{skema}', [PendaftaranSertifikasiController::class, 'create'])->name('create');
        Route::post('daftar/{periode}/{skema}', [PendaftaranSertifikasiController::class, 'store'])->name('store');
        Route::get('permohonan/{permohonan}', [PendaftaranSertifikasiController::class, 'show'])->name('show');
        Route::put('permohonan/{permohonan}/lokasi', [PendaftaranSertifikasiController::class, 'pilihLokasi'])->name('lokasi');
        Route::post('permohonan/{permohonan}/batal', [PendaftaranSertifikasiController::class, 'batal'])->name('batal');

        // Perbaiki permohonan yang ditolak (nomor & folder tetap). POST karena berisi unggahan file.
        Route::get('permohonan/{permohonan}/perbaiki', [PendaftaranSertifikasiController::class, 'perbaiki'])->name('perbaiki');
        Route::post('permohonan/{permohonan}/perbaiki', [PendaftaranSertifikasiController::class, 'simpanPerbaikan'])->name('perbaiki.store');
        Route::get('dokumen-saya/{jenis}', [PendaftaranSertifikasiController::class, 'dokumenSaya'])->name('dokumen-saya');
    });

Route::middleware('auth')->group(function () {
    // Hak akses (admin atau pemilik) diperiksa di controller.
    Route::get('dokumen/permohonan/{permohonan}/{jenis}', DokumenPermohonanController::class)->name('dokumen.permohonan');

    // Dropdown wilayah untuk alamat; kode wilayah berformat angka bertitik, mis. 35.78.01.
    Route::prefix('wilayah')->name('wilayah.')->group(function () {
        Route::get('provinsi', [WilayahController::class, 'provinsi'])->name('provinsi');
        Route::get('kota/{provinsi}', [WilayahController::class, 'kota'])->where('provinsi', '[0-9.]+')->name('kota');
        Route::get('kecamatan/{kota}', [WilayahController::class, 'kecamatan'])->where('kota', '[0-9.]+')->name('kecamatan');
        Route::get('kelurahan/{kecamatan}', [WilayahController::class, 'kelurahan'])->where('kecamatan', '[0-9.]+')->name('kelurahan');
    });

    Route::post('notifikasi/baca-semua', [NotifikasiController::class, 'bacaSemua'])->name('notifikasi.baca-semua');
    Route::post('notifikasi/{id}/baca', [NotifikasiController::class, 'baca'])->name('notifikasi.baca');
});
