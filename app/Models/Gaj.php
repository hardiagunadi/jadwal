<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Gaj extends Model
{
    protected $fillable = [
        'jenis',
        'nama_satker',
        'kode_satker',
        'bulan',
        'tahun',
        'seksi_akronim',
    ];

    protected $casts = [
        'bulan' => 'integer',
        'tahun' => 'integer',
    ];

    public static array $bulanLabels = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    public function pegawais(): HasMany
    {
        return $this->hasMany(GajPegawai::class, 'gaj_id')->orderBy('no_urut');
    }

    public function getBulanLabelAttribute(): string
    {
        return self::$bulanLabels[$this->bulan] ?? $this->bulan;
    }

    public function getPeriodeAttribute(): string
    {
        return ($this->bulan_label) . ' ' . $this->tahun;
    }

    public function getTotalBersihAttribute(): int
    {
        return $this->pegawais()->sum('jumlah_bersih');
    }
}
