<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kegiatan extends Model
{
    use HasFactory;

    protected $table = 'kegiatans';

    protected $fillable = [
        'nomor',
        'nama_kegiatan',
        'tanggal',
        'waktu',
        'tempat',
        'keterangan',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function personils()
    {
        return $this->belongsToMany(Personil::class, 'kegiatan_personil')
            ->withTimestamps();
    }

    public function getTanggalLabelAttribute(): string
    {
        if (! $this->tanggal) {
            return '-';
        }

        // Pastikan locale Carbon dan app di-set ke 'id' jika mau Indonesia
        return $this->tanggal->translatedFormat('l, d-m-Y');
    }

    public function getJudulSingkatAttribute(): string
    {
        return $this->nama_kegiatan . ' (' . $this->tanggal_label . ')';
    }
}
