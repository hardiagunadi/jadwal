<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spj_penerimas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spj_id')->constrained()->cascadeOnDelete();
            $table->foreignId('personil_id')->nullable()->constrained('personils')->nullOnDelete();
            $table->string('nama', 255);
            $table->string('no_rekening', 30)->nullable();
            $table->string('nama_bank', 50)->nullable();
            $table->bigInteger('jumlah')->default(0);
            $table->timestamps();
        });

        Schema::create('bkus', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('nomor_urut');
            $table->date('tanggal_generate');
            $table->foreignId('dpa_sub_kegiatan_id')->nullable()->constrained('dpa_sub_kegiatans')->nullOnDelete();
            $table->text('pekerjaan')->nullable();
            $table->unsignedSmallInteger('bulan');
            $table->unsignedSmallInteger('tahun');
            $table->bigInteger('penerimaan')->default(0);
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        Schema::create('bku_spj', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bku_id')->constrained()->cascadeOnDelete();
            $table->foreignId('spj_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->unique(['bku_id', 'spj_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bku_spj');
        Schema::dropIfExists('bkus');
        Schema::dropIfExists('spj_penerimas');
    }
};
