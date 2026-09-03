<?php

namespace App\Models;

use Database\Factories\AsesorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Asesor extends Model
{
    /** @use HasFactory<AsesorFactory> */
    use HasFactory;

    protected $table = 'asesor';

    protected $primaryKey = 'id_asesor';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id_user',
        'nama',
        'no_reg',
    ];

    /**
     * Akun login milik asesor ini.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }
}
