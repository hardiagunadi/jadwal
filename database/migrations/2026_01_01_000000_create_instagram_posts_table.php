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
            $table->string('status')->default('draft');
            $table->text('caption')->nullable();
            $table->string('media_path')->nullable();
            $table->dateTimeTz('scheduled_at')->nullable();
            $table->dateTimeTz('publish_attempted_at')->nullable();
            $table->dateTimeTz('published_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestampsTz();

            $table->index(['status', 'scheduled_at']);
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
