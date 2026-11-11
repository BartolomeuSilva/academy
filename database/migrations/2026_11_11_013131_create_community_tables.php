<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('community_spaces', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('slug', 100)->unique();
            $table->string('description', 255)->nullable();
            $table->boolean('staff_only')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('community_topics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('space_id')->constrained('community_spaces')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 160);
            $table->text('body');
            $table->timestamp('pinned_at')->nullable();
            $table->timestamp('edited_at')->nullable();
            $table->timestamp('last_activity_at')->useCurrent();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['space_id', 'last_activity_at']);
            $table->index('last_activity_at');
            $table->index('user_id');
        });

        Schema::create('community_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_id')->constrained('community_topics')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['topic_id', 'id']);
        });

        Schema::table('community_topics', function (Blueprint $table) {
            $table->foreignId('solution_reply_id')->nullable()->after('body')
                ->constrained('community_replies')->nullOnDelete();
        });

        Schema::create('community_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('reportable');
            $table->string('reason', 500)->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'reportable_type', 'reportable_id']);
            $table->index(['resolved_at', 'created_at']);
        });

        DB::table('community_spaces')->insert([
            ['name' => 'Avisos', 'slug' => 'avisos', 'description' => 'Novidades e comunicados da equipe.', 'staff_only' => true, 'position' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Geral', 'slug' => 'geral', 'description' => 'Dúvidas e conversas que não são de um produto específico.', 'staff_only' => false, 'position' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Apresente-se', 'slug' => 'apresente-se', 'description' => 'Conte quem você é e como usa nossos produtos.', 'staff_only' => false, 'position' => 3, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('community_topics', function (Blueprint $table) {
            $table->dropConstrainedForeignId('solution_reply_id');
        });

        Schema::dropIfExists('community_reports');
        Schema::dropIfExists('community_replies');
        Schema::dropIfExists('community_topics');
        Schema::dropIfExists('community_spaces');
    }
};
