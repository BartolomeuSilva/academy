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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);
            $table->string('slug')->unique();
            $table->string('summary', 300);
            $table->text('description')->nullable();
            $table->string('cover_path')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('position')->default(0)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
