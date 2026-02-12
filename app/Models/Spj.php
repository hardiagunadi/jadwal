<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Spj extends Model
{
    protected $fillable = [
        'surat_keluar_id',
        'dpa_rincian_belanja_id',
        'personil_id',
        'nomor_kwitansi',
        'tanggal',
        'uraian',
        'jumlah',
        'ppn',
        'pph21',
        'pph22',
        'pph23',
        'tahun',
        'catatan',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'jumlah' => 'int',
        'ppn' => 'int',
        'pph21' => 'int',
        'pph22' => 'int',
        'pph23' => 'int',
        'tahun' => 'int',
    ];

    public function suratKeluar()
    {
        return $this->belongsTo(SuratKeluar::class);
    }

    public function personil()
    {
        return $this->belongsTo(Personil::class);
    }

    public function dpaRincianBelanja()
    {
        return $this->belongsTo(DpaRincianBelanja::class);
    }

    public function getTotalPajakAttribute(): int
    {
        return $this->ppn + $this->pph21 + $this->pph22 + $this->pph23;
    }

    public function getJumlahBersihAttribute(): int
    {
        return $this->jumlah - $this->total_pajak;
    }

    public function getJumlahFormatAttribute(): string
    {
        return $this->jumlah ? number_format((int) $this->jumlah, 0, ',', '.') : '0';
    }
}
