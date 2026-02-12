<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spjs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surat_keluar_id')->nullable()->constrained('surat_keluars')->nullOnDelete();
            $table->foreignId('personil_id')->nullable()->constrained('personils')->nullOnDelete();
            $table->string('nomor_kwitansi')->nullable();
            $table->date('tanggal');
            $table->text('uraian');
            $table->bigInteger('jumlah')->nullable();
            $table->smallInteger('tahun');
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spjs');
    }
};
