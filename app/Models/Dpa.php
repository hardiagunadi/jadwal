<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Dpa extends Model
{
    protected $fillable = [
        'nomor_dpa',
        'tahun',
        'urusan',
        'bidang_urusan',
        'program',
        'kegiatan',
        'organisasi',
        'pagu_anggaran',
        'catatan',
        'seksi_akronim',
    ];

    protected $casts = [
        'tahun' => 'int',
        'pagu_anggaran' => 'int',
    ];

    public function subKegiatans(): HasMany
    {
        return $this->hasMany(DpaSubKegiatan::class);
    }

    public function rincianBelanjas(): HasManyThrough
    {
        return $this->hasManyThrough(DpaRincianBelanja::class, DpaSubKegiatan::class);
    }

    public function getNomorLabelAttribute(): string
    {
        return $this->nomor_dpa . ' (' . $this->tahun . ')';
    }

    public function getPaguFormatAttribute(): string
    {
        return 'Rp ' . number_format((int) $this->pagu_anggaran, 0, ',', '.');
    }
}
