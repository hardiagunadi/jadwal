<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DpaRincianBelanja extends Model
{
    protected $fillable = [
        'dpa_sub_kegiatan_id',
        'kode_rekening',
        'uraian',
        'volume',
        'satuan',
        'harga',
        'ppn_persen',
        'jumlah',
    ];

    protected $casts = [
        'volume' => 'float',
        'harga' => 'int',
        'ppn_persen' => 'float',
        'jumlah' => 'int',
    ];

    public function subKegiatan(): BelongsTo
    {
        return $this->belongsTo(DpaSubKegiatan::class, 'dpa_sub_kegiatan_id');
    }

    public function spjs(): HasMany
    {
        return $this->hasMany(Spj::class);
    }

    public function getTotalSpjAttribute(): int
    {
        return (int) $this->spjs()->sum('jumlah');
    }

    public function getSisaAnggaranAttribute(): int
    {
        return $this->jumlah - $this->total_spj;
    }

    public function getPaguFormatAttribute(): string
    {
        return 'Rp ' . number_format((int) $this->jumlah, 0, ',', '.');
    }

    public function getTotalSpjFormatAttribute(): string
    {
        return 'Rp ' . number_format($this->total_spj, 0, ',', '.');
    }

    public function getSisaAnggaranFormatAttribute(): string
    {
        return 'Rp ' . number_format($this->sisa_anggaran, 0, ',', '.');
    }

    public function getLabelAttribute(): string
    {
        return '[' . $this->kode_rekening . '] ' . $this->uraian;
    }

    public function getJumlahFormatAttribute(): string
    {
        return 'Rp ' . number_format((int) $this->jumlah, 0, ',', '.');
    }
}
