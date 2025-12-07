<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instagram_posts', function (Blueprint $table): void {
            $table->id();
            $table->string('media_type');
            $table->text('caption_prompt')->nullable();
            $table->text('generated_caption')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->string('status')->default('draft');
            $table->string('storage_path')->nullable();
            $table->unsignedBigInteger('template_id')->nullable();
            $table->string('ig_publish_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instagram_posts');
    }
};
