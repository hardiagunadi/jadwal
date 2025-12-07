<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kegiatans', function (Blueprint $table) {
            $table->text('generated_caption')->nullable()->after('keterangan');
        });

        Schema::create('caption_generation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kegiatan_id')->nullable()->constrained('kegiatans')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('keywords')->nullable();
            $table->text('brand_style')->nullable();
            $table->integer('max_length')->nullable();
            $table->text('generated_caption')->nullable();
            $table->string('status', 50)->default('success');
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caption_generation_logs');

        Schema::table('kegiatans', function (Blueprint $table) {
            $table->dropColumn('generated_caption');
        });
    }
};
