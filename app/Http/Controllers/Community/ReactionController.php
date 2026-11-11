<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use App\Models\CommunityMessage;
use App\Models\CommunityReaction;
use App\Models\CommunitySpace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReactionController extends Controller
{
    /**
     * Add the emoji to the message, or remove it if the user had already reacted with it.
     */
    public function store(Request $request, CommunitySpace $space, CommunityMessage $message): RedirectResponse
    {
        $validated = $request->validate(['emoji' => ['required', 'string', Rule::in(CommunityMessage::REACTIONS)]]);

        $existing = $message->reactions()->whereBelongsTo($request->user())->where('emoji', $validated['emoji'])->first();

        if ($existing) {
            $existing->delete();
        } else {
            $reaction = new CommunityReaction;
            $reaction->emoji = $validated['emoji'];
            $reaction->user()->associate($request->user());
            $message->reactions()->save($reaction);
        }

        return back();
    }
}
