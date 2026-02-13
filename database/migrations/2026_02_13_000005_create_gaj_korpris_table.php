<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gaj_korpris', function (Blueprint $table) {
            $table->id();
            $table->string('nip', 20)->unique();
            $table->string('nama', 100)->nullable();
            $table->unsignedInteger('jumlah')->default(20000);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gaj_korpris');
    }
};
