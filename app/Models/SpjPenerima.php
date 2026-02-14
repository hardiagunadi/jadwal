<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpjPenerima extends Model
{
    protected $fillable = [
        'spj_id',
        'personil_id',
        'nama',
        'no_rekening',
        'nama_bank',
        'jumlah',
    ];

    protected $casts = [
        'jumlah' => 'int',
    ];

    public function spj(): BelongsTo
    {
        return $this->belongsTo(Spj::class);
    }

    public function personil(): BelongsTo
    {
        return $this->belongsTo(Personil::class);
    }
}
