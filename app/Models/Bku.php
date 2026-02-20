<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Bku extends Model
{
    protected $fillable = [
        'nomor_urut',
        'tanggal_generate',
        'dpa_sub_kegiatan_id',
        'pekerjaan',
        'bulan',
        'tahun',
        'penerimaan',
        'catatan',
    ];

    protected $casts = [
        'tanggal_generate' => 'date',
        'bulan' => 'int',
        'tahun' => 'int',
        'penerimaan' => 'int',
    ];

    public function dpaSubKegiatan(): BelongsTo
    {
        return $this->belongsTo(DpaSubKegiatan::class);
    }

    public function spjs(): BelongsToMany
    {
        return $this->belongsToMany(Spj::class, 'bku_spj')->withPivot('created_at');
    }

    public function getNamaLabelAttribute(): string
    {
        $tgl = $this->tanggal_generate?->format('Y-m-d') ?? '';

        return "{$this->nomor_urut}-BKU-{$tgl}";
    }

    public function getTotalPengeluaranAttribute(): int
    {
        return (int) $this->spjs()->sum('jumlah');
    }

    public function getSaldoAttribute(): int
    {
        return $this->penerimaan - $this->total_pengeluaran;
    }

    public function syncPenerimaan(): void
    {
        $this->update(['penerimaan' => $this->total_pengeluaran]);
    }
}
