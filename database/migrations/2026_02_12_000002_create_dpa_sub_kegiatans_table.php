<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dpa_sub_kegiatans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dpa_id')->constrained('dpas')->cascadeOnDelete();
            $table->string('kode');
            $table->string('nama');
            $table->string('sumber_pendanaan')->nullable();
            $table->bigInteger('pagu')->default(0);
            $table->timestamps();

            $table->unique(['dpa_id', 'kode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dpa_sub_kegiatans');
    }
};
