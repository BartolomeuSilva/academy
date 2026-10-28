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
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->uuid()->unique();
            $table->string('status')->default('uploading');
            $table->string('original_name');
            $table->string('original_path')->nullable();
            $table->unsignedBigInteger('original_size');
            $table->string('upload_id')->nullable();
            $table->string('playlist_path')->nullable();
            $table->json('renditions')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('poster_path')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('processing_started_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['lesson_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
