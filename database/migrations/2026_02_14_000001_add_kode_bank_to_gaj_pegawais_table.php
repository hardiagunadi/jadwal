<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gaj_pegawais', function (Blueprint $table) {
            $table->string('kode_bank', 20)->nullable()->after('no_rekening');
        });
    }

    public function down(): void
    {
        Schema::table('gaj_pegawais', function (Blueprint $table) {
            $table->dropColumn('kode_bank');
        });
    }
};
