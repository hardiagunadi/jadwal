<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seksi_moduls', function (Blueprint $table) {
            $table->id();
            $table->string('seksi_akronim', 50)->comment('Akronim seksi, misal: ekbang, pmd, umum');
            $table->string('seksi_label', 100)->comment('Nama tampilan di menu, misal: Seksi Ekbang');
            $table->string('modul_key', 80)->comment('Key modul, misal: spj, dpa, agenda');
            $table->boolean('aktif')->default(true);
            $table->timestamps();

            $table->unique(['seksi_akronim', 'modul_key']);
            $table->index('seksi_akronim');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seksi_moduls');
    }
};
