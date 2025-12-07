<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('instagram_posts', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('media_path');
            $table->string('processed_path')->nullable();
            $table->foreignId('instagram_template_id')->nullable()->constrained('instagram_templates');
            $table->text('caption')->nullable();
            $table->text('keywords')->nullable();
            $table->timestamp('publish_at')->nullable();
            $table->string('container_id')->nullable();
            $table->string('publish_id')->nullable();
            $table->string('status')->default('draft');
            $table->json('response_payload')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instagram_posts');
    }
};
