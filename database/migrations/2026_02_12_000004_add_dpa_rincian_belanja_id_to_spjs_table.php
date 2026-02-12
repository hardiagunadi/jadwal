<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spjs', function (Blueprint $table) {
            $table->foreignId('dpa_rincian_belanja_id')
                ->nullable()
                ->after('surat_keluar_id')
                ->constrained('dpa_rincian_belanjas')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('spjs', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\DpaRincianBelanja::class);
            $table->dropColumn('dpa_rincian_belanja_id');
        });
    }
};
