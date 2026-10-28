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
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->string('title', 150);
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('status')->default('draft');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['course_id', 'slug']);
            $table->index(['module_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
