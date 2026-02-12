<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dpa_rincian_belanjas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dpa_sub_kegiatan_id')->constrained('dpa_sub_kegiatans')->cascadeOnDelete();
            $table->string('kode_rekening');
            $table->string('uraian');
            $table->decimal('volume', 15, 2)->nullable();
            $table->string('satuan')->nullable();
            $table->bigInteger('harga')->nullable();
            $table->decimal('ppn_persen', 5, 2)->default(0);
            $table->bigInteger('jumlah')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dpa_rincian_belanjas');
    }
};
