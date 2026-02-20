<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gaj_pegawais', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gaj_id')->constrained('gajs')->cascadeOnDelete();
            $table->unsignedSmallInteger('no_urut')->default(0);
            $table->string('nama', 100);
            $table->string('tanggal_lahir', 12)->nullable();
            $table->string('nip', 20)->nullable();
            $table->string('npwp', 20)->nullable();
            $table->string('status_kawin', 10)->nullable()->comment('TK-0, K-1, K-2, dll');
            $table->string('golongan', 30)->nullable()->comment('Misal: PPPK - 07 MKG: 3');
            $table->unsignedInteger('gaji_pokok')->default(0);
            $table->unsignedInteger('jumlah_penghasilan')->default(0)->comment('Jumlah gaji + tunjangan');
            $table->unsignedInteger('jml_kotor')->default(0)->comment('Jumlah kotor sebelum potongan');
            $table->unsignedInteger('pot_bpjs_kes')->default(0);
            $table->unsignedInteger('jml_potongan')->default(0);
            $table->unsignedInteger('jumlah_bersih')->default(0);
            $table->string('no_rekening', 30)->nullable();
            $table->timestamps();

            $table->index('gaj_id');
            $table->index('nip');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gaj_pegawais');
    }
};
