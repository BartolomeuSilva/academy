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
        Schema::create('lesson_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->string('kind');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['lesson_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_attachments');
    }
};
