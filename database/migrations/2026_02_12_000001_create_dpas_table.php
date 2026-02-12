<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dpas', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_dpa')->unique();
            $table->smallInteger('tahun');
            $table->string('urusan')->nullable();
            $table->string('bidang_urusan')->nullable();
            $table->string('program')->nullable();
            $table->text('kegiatan')->nullable();
            $table->string('organisasi')->nullable();
            $table->bigInteger('pagu_anggaran')->default(0);
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dpas');
    }
};
