<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah kolom bank ke personils
        Schema::table('personils', function (Blueprint $table) {
            $table->string('no_rekening', 30)->nullable()->after('no_wa');
            $table->string('kode_bank', 20)->nullable()->after('no_rekening');
        });

        // 2. Migrasi data: ambil nilai terbaru per NIP dari gaj_pegawais
        $latestBank = DB::table('gaj_pegawais')
            ->select('nip', 'no_rekening', 'kode_bank')
            ->whereNotNull('nip')
            ->where(function ($q) {
                $q->whereNotNull('no_rekening')->where('no_rekening', '!=', '')
                  ->orWhereNotNull('kode_bank');
            })
            ->orderBy('id', 'desc')
            ->get()
            ->unique('nip');

        foreach ($latestBank as $row) {
            DB::table('personils')
                ->where('nip', $row->nip)
                ->update(array_filter([
                    'no_rekening' => $row->no_rekening ?: null,
                    'kode_bank'   => $row->kode_bank ?: null,
                ], fn ($v) => $v !== null));
        }

        // 3. Drop kolom bank dari gaj_pegawais
        Schema::table('gaj_pegawais', function (Blueprint $table) {
            $table->dropColumn(['no_rekening', 'kode_bank']);
        });
    }

    public function down(): void
    {
        // Kembalikan kolom ke gaj_pegawais
        Schema::table('gaj_pegawais', function (Blueprint $table) {
            $table->string('no_rekening', 30)->nullable()->after('jumlah_bersih');
            $table->string('kode_bank', 20)->nullable()->after('no_rekening');
        });

        // Migrasi balik dari personils ke gaj_pegawais
        $personils = DB::table('personils')
            ->select('nip', 'no_rekening', 'kode_bank')
            ->whereNotNull('nip')
            ->where(function ($q) {
                $q->whereNotNull('no_rekening')->where('no_rekening', '!=', '')
                  ->orWhereNotNull('kode_bank');
            })
            ->get();

        foreach ($personils as $row) {
            DB::table('gaj_pegawais')
                ->where('nip', $row->nip)
                ->update([
                    'no_rekening' => $row->no_rekening,
                    'kode_bank'   => $row->kode_bank,
                ]);
        }

        // Drop dari personils
        Schema::table('personils', function (Blueprint $table) {
            $table->dropColumn(['no_rekening', 'kode_bank']);
        });
    }
};
