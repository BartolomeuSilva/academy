<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use App\Models\CommunityReply;
use App\Models\CommunityTopic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class TopicSolutionController extends Controller
{
    /**
     * Mark the reply that solved the topic.
     */
    public function store(CommunityTopic $topic, CommunityReply $reply): RedirectResponse
    {
        Gate::authorize('solve', $topic);

        $topic->forceFill(['solution_reply_id' => $reply->id])->save();

        return back();
    }

    /**
     * Unmark the topic's solution.
     */
    public function destroy(CommunityTopic $topic): RedirectResponse
    {
        Gate::authorize('solve', $topic);

        $topic->forceFill(['solution_reply_id' => null])->save();

        return back();
    }
}
