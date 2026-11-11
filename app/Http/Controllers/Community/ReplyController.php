<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use App\Http\Requests\Community\ReplyRequest;
use App\Models\CommunityReply;
use App\Models\CommunityTopic;
use App\Notifications\CommunityReplyPosted;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ReplyController extends Controller
{
    /**
     * Reply to a topic and let its author know.
     */
    public function store(ReplyRequest $request, CommunityTopic $topic): RedirectResponse
    {
        $reply = new CommunityReply(['body' => $request->string('body')->trim()->value()]);
        $reply->user()->associate($request->user());

        DB::transaction(function () use ($topic, $reply) {
            $topic->replies()->save($reply);
            $topic->forceFill(['last_activity_at' => now()])->save();
        });

        if ($topic->user_id !== $reply->user_id) {
            $topic->user->notify(new CommunityReplyPosted($reply));
        }

        $lastPage = (int) ceil($topic->replies()->count() / TopicController::REPLIES_PER_PAGE);

        return to_route('community.topics.show', [$topic, 'page' => max($lastPage, 1)]);
    }

    /**
     * Edit the user's own reply.
     */
    public function update(ReplyRequest $request, CommunityTopic $topic, CommunityReply $reply): RedirectResponse
    {
        Gate::authorize('update', $reply);

        $reply->body = $request->string('body')->trim()->value();

        if ($reply->isDirty('body')) {
            $reply->edited_at = now();
            $reply->save();
        }

        return back();
    }

    /**
     * Delete a reply: authors delete their own, admins any. A deleted answer stops being the topic's solution.
     */
    public function destroy(CommunityTopic $topic, CommunityReply $reply): RedirectResponse
    {
        Gate::authorize('delete', $reply);

        DB::transaction(function () use ($topic, $reply) {
            if ($topic->solution_reply_id === $reply->id) {
                $topic->forceFill(['solution_reply_id' => null])->save();
            }

            $reply->delete();
        });

        return back();
    }
}
