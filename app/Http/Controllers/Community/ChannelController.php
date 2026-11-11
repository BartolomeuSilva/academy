<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use App\Models\CommunityMessage;
use App\Models\CommunityReaction;
use App\Models\CommunitySpace;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ChannelController extends Controller
{
    /**
     * Messages loaded at a time, newest first, when opening a channel or loading earlier ones.
     */
    public const int PAGE_SIZE = 50;

    /**
     * Open the first channel the student may write in, or the first one.
     */
    public function index(Request $request): RedirectResponse
    {
        $channels = $this->channels();
        $channel = $channels->first(fn (CommunitySpace $space) => $request->user()->can('create', [CommunityMessage::class, $space]))
            ?? $channels->first();

        abort_if($channel === null, 404);

        return to_route('community.channels.show', $channel->slug);
    }

    /**
     * Show a channel's conversation: the latest messages, earlier ones before `antes`, those from `mensagem` on, or a search.
     */
    public function show(Request $request, CommunitySpace $space): Response
    {
        $user = $request->user();
        $search = $request->string('busca')->trim()->limit(100, '')->toString();
        $before = $request->integer('antes') ?: null;
        $from = $request->integer('mensagem') ?: null;

        $query = $space->messages()
            ->with(['user:id,name,role,avatar_path', 'images', 'replyTo' => fn ($reply) => $reply->withTrashed()->with('user:id,name')])
            ->when($search !== '', fn (Builder $query) => $query->whereLike('body', "%{$search}%"));

        if ($from !== null) {
            $messages = $query->where('id', '>=', $from)->oldest('id')->limit(self::PAGE_SIZE * 2)->get();
            $hasOlder = $space->messages()->where('id', '<', $from)->exists();
        } else {
            $messages = $query->when($before, fn (Builder $query, int $before) => $query->where('id', '<', $before))
                ->latest('id')
                ->limit(self::PAGE_SIZE + 1)
                ->get();
            $hasOlder = $messages->count() > self::PAGE_SIZE;
            $messages = $messages->take(self::PAGE_SIZE)->reverse()->values();
        }

        $reactions = CommunityReaction::query()
            ->whereIn('message_id', $messages->modelKeys())
            ->get(['message_id', 'user_id', 'emoji'])
            ->groupBy('message_id');

        return Inertia::render('community/Channel', [
            'channels' => $this->channels()->map(fn (CommunitySpace $channel) => [
                'id' => $channel->id,
                'name' => $channel->name,
                'slug' => $channel->slug,
                'emoji' => $channel->emoji,
                'staff_only' => $channel->staff_only,
            ])->values(),
            'channel' => [
                'id' => $space->id,
                'name' => $space->name,
                'slug' => $space->slug,
                'emoji' => $space->emoji,
                'description' => $space->description,
                'staff_only' => $space->staff_only,
                'messages_count' => $space->messages()->count(),
                'can_post' => $user->can('create', [CommunityMessage::class, $space]),
            ],
            'messages' => $messages->map(fn (CommunityMessage $message) => $this->message($message, $user, $reactions->get($message->id) ?? new Collection))->values(),
            'has_older' => $hasOlder,
            'filters' => ['search' => $search, 'before' => $before, 'from' => $from],
            'reactions' => CommunityMessage::REACTIONS,
        ]);
    }

    /**
     * @return Collection<int, CommunitySpace>
     */
    private function channels(): Collection
    {
        return CommunitySpace::query()->orderBy('position')->orderBy('id')->get();
    }

    /**
     * @param  Collection<int, CommunityReaction>  $reactions
     * @return array<string, mixed>
     */
    private function message(CommunityMessage $message, User $user, Collection $reactions): array
    {
        return [
            'id' => $message->id,
            'body' => $user->can('update', $message) ? $message->body : null,
            'body_html' => $message->bodyHtml(),
            'images' => $message->imagesForDisplay(),
            'author' => ['name' => $message->user->name, 'is_team' => $message->user->isAdmin(), 'avatar' => $message->user->avatar],
            'created_at' => $message->created_at?->toIso8601String(),
            'edited' => $message->edited_at !== null,
            'reply_to' => $message->replyTo ? [
                'id' => $message->replyTo->id,
                'author' => $message->replyTo->user->name,
                'excerpt' => $message->replyTo->trashed() ? 'Mensagem removida' : $message->replyTo->excerpt(),
            ] : null,
            'reactions' => $reactions->groupBy('emoji')->map(fn (Collection $group, string $emoji) => [
                'emoji' => $emoji,
                'count' => $group->count(),
                'mine' => $group->contains('user_id', $user->id),
            ])->values()->all(),
            'can' => [
                'update' => $user->can('update', $message),
                'delete' => $user->can('delete', $message),
                'report' => $user->can('report', $message),
            ],
        ];
    }
}
