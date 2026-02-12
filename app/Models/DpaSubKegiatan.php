<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DpaSubKegiatan extends Model
{
    protected $fillable = [
        'dpa_id',
        'kode',
        'nama',
        'sumber_pendanaan',
        'pagu',
    ];

    protected $casts = [
        'pagu' => 'int',
    ];

    public function dpa(): BelongsTo
    {
        return $this->belongsTo(Dpa::class);
    }

    public function rincianBelanjas(): HasMany
    {
        return $this->hasMany(DpaRincianBelanja::class);
    }

    public function getTotalSpjAttribute(): int
    {
        return (int) $this->rincianBelanjas()
            ->join('spjs', 'dpa_rincian_belanjas.id', '=', 'spjs.dpa_rincian_belanja_id')
            ->sum('spjs.jumlah');
    }

    public function getSisaAnggaranAttribute(): int
    {
        return $this->pagu - $this->total_spj;
    }

    public function getLabelAttribute(): string
    {
        return $this->kode . ' – ' . $this->nama;
    }
}
