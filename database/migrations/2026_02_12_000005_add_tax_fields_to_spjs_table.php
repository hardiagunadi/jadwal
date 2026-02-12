<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spjs', function (Blueprint $table) {
            $table->unsignedBigInteger('ppn')->default(0)->after('jumlah');
            $table->unsignedBigInteger('pph21')->default(0)->after('ppn');
            $table->unsignedBigInteger('pph22')->default(0)->after('pph21');
            $table->unsignedBigInteger('pph23')->default(0)->after('pph22');
        });
    }

    public function down(): void
    {
        Schema::table('spjs', function (Blueprint $table) {
            $table->dropColumn(['ppn', 'pph21', 'pph22', 'pph23']);
        });
    }
};
