<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gajs', function (Blueprint $table) {
            $table->id();
            $table->enum('jenis', ['pns', 'pppk'])->comment('Jenis: PNS atau PPPK');
            $table->string('nama_satker', 150)->comment('Nama satker, misal: KECAMATAN WATUMALANG');
            $table->string('kode_satker', 30)->nullable()->comment('Kode satker, misal: D40100702500001');
            $table->unsignedTinyInteger('bulan')->comment('Bulan 1-12');
            $table->unsignedSmallInteger('tahun');
            $table->string('seksi_akronim', 50)->nullable()->comment('Akronim seksi pemilik data gaji');
            $table->timestamps();

            $table->unique(['jenis', 'kode_satker', 'bulan', 'tahun']);
            $table->index(['bulan', 'tahun']);
            $table->index('seksi_akronim');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gajs');
    }
};
