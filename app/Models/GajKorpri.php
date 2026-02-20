<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GajKorpri extends Model
{
    protected $fillable = ['nip', 'nama', 'jumlah'];

    protected $casts = [
        'jumlah' => 'integer',
    ];
}
