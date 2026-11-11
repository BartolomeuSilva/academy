<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use App\Http\Requests\Community\StoreMessageRequest;
use App\Http\Requests\Community\UpdateMessageRequest;
use App\Models\CommunityMessage;
use App\Models\CommunitySpace;
use App\Notifications\CommunityMessageReplied;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class MessageController extends Controller
{
    /**
     * Post a message in the channel, telling the author of a quoted message.
     */
    public function store(StoreMessageRequest $request, CommunitySpace $space): RedirectResponse
    {
        Gate::authorize('create', [CommunityMessage::class, $space]);

        $message = new CommunityMessage(['body' => $request->string('body')->trim()->value()]);
        $message->user()->associate($request->user());
        $message->reply_to_id = $request->integer('reply_to_id') ?: null;
        $space->messages()->save($message);
        $message->attachImages($request->file('images', []));

        $quoted = $message->replyTo;

        if ($quoted !== null && $quoted->user_id !== $message->user_id) {
            $quoted->user->notify(new CommunityMessageReplied($message));
        }

        return to_route('community.channels.show', $space->slug);
    }

    /**
     * Edit the user's own message.
     */
    public function update(UpdateMessageRequest $request, CommunitySpace $space, CommunityMessage $message): RedirectResponse
    {
        $message->body = $request->string('body')->trim()->value();

        if ($message->isDirty('body')) {
            $message->edited_at = now();
            $message->save();
        }

        return back();
    }

    /**
     * Delete a message: authors delete their own, admins any.
     */
    public function destroy(CommunitySpace $space, CommunityMessage $message): RedirectResponse
    {
        Gate::authorize('delete', $message);

        $message->delete();

        return back();
    }
}
