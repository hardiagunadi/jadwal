<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Personil extends Model
{
    use HasFactory;

    protected $table = 'personils';

    protected $fillable = [
        'nama',
		'nip', 
        'jabatan',
        'no_wa',
        'keterangan',
    ];

    public function kegiatans()
    {
        return $this->belongsToMany(Kegiatan::class, 'kegiatan_personil')
            ->withTimestamps();
    }

    public function getLabelAttribute(): string
    {
        $parts = [$this->nama];

        if ($this->jabatan) {
            $parts[] = '(' . $this->jabatan . ')';
        }

        return implode(' ', $parts);
    }
}
