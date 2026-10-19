<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Concerns\HasUlids;

/**
 * Model yang tampil di URL memakai kolom `ulid` (kode acak) sebagai route key, bukan id berurutan.
 * Primary key tetap angka; ULID dibuat otomatis saat data dibuat, dan kode yang tidak valid langsung 404.
 */
trait PunyaKodeUrl
{
    use HasUlids;

    /**
     * Hanya kolom ulid yang diisi otomatis, primary key tetap auto-increment.
     *
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }
}
