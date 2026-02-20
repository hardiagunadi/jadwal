<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class SeksiModul extends Model
{
    protected $fillable = [
        'seksi_akronim',
        'seksi_label',
        'modul_key',
        'aktif',
    ];

    protected $casts = [
        'aktif' => 'boolean',
    ];

    // ─── Daftar modul yang tersedia di sistem ─────────────────────────────────

    /**
     * Daftarkan modul baru di sini agar muncul di halaman pengaturan.
     * Key = modul_key yang digunakan di resource ($modulKey).
     */
    public static function availableModuls(): array
    {
        return [
            'dpa'          => 'DPA (Dokumen Pelaksanaan Anggaran)',
            'spj'          => 'SPJ Kwitansi Dinas',
            'surat-keluar' => 'Surat Keluar',
            'agenda'       => 'Agenda Surat Masuk',
            'agenda-pkk'   => 'Agenda PKK Kecamatan',
            'personil'     => 'Personil',
            'gaji'         => 'Daftar Gaji (PNS & PPPK)',
        ];
    }

    // ─── Cache helpers ────────────────────────────────────────────────────────

    public static function clearCache(): void
    {
        Cache::forget('seksi_moduls_all');
    }

    /** Ambil semua row aktif, di-cache per request. */
    protected static function allAktif(): \Illuminate\Support\Collection
    {
        if (! Schema::hasTable('seksi_moduls')) {
            return collect();
        }

        return Cache::remember('seksi_moduls_all', 60, function () {
            return static::where('aktif', true)->get();
        });
    }

    // ─── Query helpers ────────────────────────────────────────────────────────

    /**
     * Apakah modul $modulKey aktif untuk seksi $akronim?
     */
    public static function aktifUntuk(string $akronim, string $modulKey): bool
    {
        $akronim = strtolower(trim($akronim));

        return static::allAktif()
            ->where('modul_key', $modulKey)
            ->contains(fn ($row) => strtolower(trim($row->seksi_akronim)) === $akronim);
    }

    /**
     * Label seksi (nama tampilan di menu) untuk $akronim.
     * Jika tidak ada, kembalikan null.
     */
    public static function labelSeksi(string $akronim): ?string
    {
        $akronim = strtolower(trim($akronim));

        $row = static::allAktif()
            ->first(fn ($row) => strtolower(trim($row->seksi_akronim)) === $akronim);

        return $row?->seksi_label;
    }

    /**
     * Daftar seksi yang memiliki modul $modulKey aktif.
     * @return array<string> akronim
     */
    public static function seksiDenganModul(string $modulKey): array
    {
        return static::allAktif()
            ->where('modul_key', $modulKey)
            ->pluck('seksi_akronim')
            ->map(fn ($v) => strtolower(trim($v)))
            ->all();
    }

    // ─── Cache invalidation ───────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::saved(fn () => static::clearCache());
        static::deleted(fn () => static::clearCache());
    }
}
