<?php

namespace App\Models;

use Database\Factories\TukFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tuk extends Model
{
    /** @use HasFactory<TukFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'tuk';

    protected $primaryKey = 'id_tuk';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nama',
        'alamat',
    ];
}
