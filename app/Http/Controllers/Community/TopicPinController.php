<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use App\Models\CommunityTopic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class TopicPinController extends Controller
{
    /**
     * Pin the topic to the top of the list (team only).
     */
    public function store(CommunityTopic $topic): RedirectResponse
    {
        Gate::authorize('moderate', $topic);

        $topic->forceFill(['pinned_at' => now()])->save();

        return back();
    }

    /**
     * Unpin the topic (team only).
     */
    public function destroy(CommunityTopic $topic): RedirectResponse
    {
        Gate::authorize('moderate', $topic);

        $topic->forceFill(['pinned_at' => null])->save();

        return back();
    }
}
