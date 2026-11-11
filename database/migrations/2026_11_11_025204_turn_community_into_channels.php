<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The community becomes a chat: students talk in channels the team creates instead of opening topics.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('community_spaces', function (Blueprint $table) {
            $table->string('emoji', 16)->nullable()->after('slug');
        });

        foreach (['avisos' => '📣', 'geral' => '💬', 'apresente-se' => '👋'] as $slug => $emoji) {
            DB::table('community_spaces')->where('slug', $slug)->update(['emoji' => $emoji]);
        }

        Schema::create('community_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('space_id')->constrained('community_spaces')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reply_to_id')->nullable()->constrained('community_messages')->nullOnDelete();
            $table->text('body');
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['space_id', 'id']);
            $table->index('user_id');
        });

        Schema::create('community_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('community_messages')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('emoji', 16);
            $table->timestamps();

            $table->unique(['message_id', 'user_id', 'emoji']);
        });

        DB::table('community_reports')->whereIn('reportable_type', ['community_topic', 'community_reply'])->delete();
        DB::table('community_images')->whereIn('imageable_type', ['community_topic', 'community_reply'])->delete();

        Schema::table('community_topics', function (Blueprint $table) {
            $table->dropConstrainedForeignId('solution_reply_id');
        });
        Schema::dropIfExists('community_replies');
        Schema::dropIfExists('community_topics');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('community_reactions');
        Schema::dropIfExists('community_messages');

        Schema::table('community_spaces', function (Blueprint $table) {
            $table->dropColumn('emoji');
        });
    }
};
