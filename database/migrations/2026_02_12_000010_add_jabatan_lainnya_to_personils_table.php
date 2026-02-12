<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personils', function (Blueprint $table) {
            $table->string('jabatan_lainnya', 100)->nullable()->after('jabatan_akronim')
                ->comment('Jabatan fungsional anggaran: PA, KPA, Bendahara Pengeluaran, PPTK');
        });
    }

    public function down(): void
    {
        Schema::table('personils', function (Blueprint $table) {
            $table->dropColumn('jabatan_lainnya');
        });
    }
};
