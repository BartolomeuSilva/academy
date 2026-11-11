<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Controller;
use App\Http\Requests\Community\StoreTopicRequest;
use App\Http\Requests\Community\UpdateTopicRequest;
use App\Models\CommunityReply;
use App\Models\CommunitySpace;
use App\Models\CommunityTopic;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TopicController extends Controller
{
    public const int TOPICS_PER_PAGE = 20;

    public const int REPLIES_PER_PAGE = 30;

    /**
     * Filters of the topic list, keyed by their value in the `filtro` query parameter.
     */
    public const array FILTERS = ['recentes', 'sem-resposta', 'meus'];

    /**
     * List topics, newest activity first, optionally by space, filter and search.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $spaces = $this->spaces();
        $space = $spaces->firstWhere('slug', $request->string('espaco')->toString());
        $filter = in_array($request->query('filtro'), self::FILTERS, true) ? $request->query('filtro') : 'recentes';
        $search = $request->string('busca')->trim()->limit(100, '')->toString();

        $topics = CommunityTopic::query()
            ->with(['user:id,name,role', 'space:id,name,slug'])
            ->withCount('replies')
            ->when($space, fn (Builder $query, CommunitySpace $space) => $query->whereBelongsTo($space, 'space'))
            ->when($filter === 'sem-resposta', fn (Builder $query) => $query->doesntHave('replies'))
            ->when($filter === 'meus', fn (Builder $query) => $query->whereBelongsTo($user))
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->whereLike('title', "%{$search}%")
                ->orWhereLike('body', "%{$search}%")))
            ->orderByRaw('pinned_at is null')
            ->orderByDesc('last_activity_at')
            ->orderByDesc('id')
            ->paginate(self::TOPICS_PER_PAGE)
            ->withQueryString()
            ->through(fn (CommunityTopic $topic) => [
                'id' => $topic->id,
                'title' => $topic->title,
                'excerpt' => Str::limit(Str::squish(strip_tags($topic->bodyHtml())), 160),
                'space' => ['name' => $topic->space->name, 'slug' => $topic->space->slug],
                'author' => $this->author($topic->user),
                'replies_count' => (int) $topic->replies_count,
                'solved' => $topic->solution_reply_id !== null,
                'pinned' => $topic->pinned_at !== null,
                'created_at' => $topic->created_at?->toIso8601String(),
                'last_activity_at' => $topic->last_activity_at->toIso8601String(),
            ]);

        return Inertia::render('community/Index', [
            'spaces' => $spaces->map(fn (CommunitySpace $space) => [
                'id' => $space->id,
                'name' => $space->name,
                'slug' => $space->slug,
                'description' => $space->description,
                'topics_count' => (int) $space->topics_count,
                'can_post' => $user->can('create', [CommunityTopic::class, $space]),
            ])->values(),
            'topics' => $topics,
            'filters' => ['space' => $space?->slug, 'filter' => $filter, 'search' => $search],
        ]);
    }

    /**
     * Show the form to start a topic, optionally in the space the student came from.
     */
    public function create(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('community/Create', [
            'spaces' => $this->spaces()
                ->filter(fn (CommunitySpace $space) => $user->can('create', [CommunityTopic::class, $space]))
                ->map(fn (CommunitySpace $space) => ['id' => $space->id, 'name' => $space->name, 'slug' => $space->slug])
                ->values(),
            'space' => $request->string('espaco')->toString() ?: null,
        ]);
    }

    /**
     * Start a topic in a space.
     */
    public function store(StoreTopicRequest $request): RedirectResponse
    {
        $space = CommunitySpace::query()->findOrFail($request->integer('space_id'));
        Gate::authorize('create', [CommunityTopic::class, $space]);

        $topic = new CommunityTopic([
            'title' => $request->string('title')->squish()->value(),
            'body' => $request->string('body')->trim()->value(),
        ]);
        $topic->space()->associate($space);
        $topic->user()->associate($request->user());
        $topic->last_activity_at = now();
        $topic->save();

        return to_route('community.topics.show', $topic);
    }

    /**
     * Show a topic with its answer first and the replies in order.
     */
    public function show(Request $request, CommunityTopic $topic): Response
    {
        $user = $request->user();
        $topic->load(['user:id,name,role', 'space:id,name,slug', 'solution.user:id,name,role']);

        $replies = $topic->replies()
            ->with('user:id,name,role')
            ->oldest('id')
            ->paginate(self::REPLIES_PER_PAGE)
            ->through(fn (CommunityReply $reply) => $this->reply($reply, $user, $topic));

        return Inertia::render('community/Show', [
            'topic' => [
                'id' => $topic->id,
                'title' => $topic->title,
                'body' => $user->can('update', $topic) ? $topic->body : null,
                'body_html' => $topic->bodyHtml(),
                'space' => ['id' => $topic->space->id, 'name' => $topic->space->name, 'slug' => $topic->space->slug],
                'author' => $this->author($topic->user),
                'pinned' => $topic->pinned_at !== null,
                'edited' => $topic->edited_at !== null,
                'created_at' => $topic->created_at?->toIso8601String(),
                'can' => [
                    'update' => $user->can('update', $topic),
                    'delete' => $user->can('delete', $topic),
                    'solve' => $user->can('solve', $topic),
                    'moderate' => $user->can('moderate', $topic),
                    'report' => $user->can('report', $topic),
                ],
            ],
            'solution' => $topic->solution ? $this->reply($topic->solution, $user, $topic) : null,
            'replies' => $replies,
            'spaces' => $user->can('moderate', $topic)
                ? $this->spaces()->map(fn (CommunitySpace $space) => ['id' => $space->id, 'name' => $space->name])->values()
                : [],
        ]);
    }

    /**
     * Edit the title and message of the user's own topic.
     */
    public function update(UpdateTopicRequest $request, CommunityTopic $topic): RedirectResponse
    {
        $topic->fill([
            'title' => $request->string('title')->squish()->value(),
            'body' => $request->string('body')->trim()->value(),
        ]);

        if ($topic->isDirty()) {
            $topic->edited_at = now();
            $topic->save();
        }

        return back();
    }

    /**
     * Move a topic to another space (team only).
     */
    public function move(Request $request, CommunityTopic $topic): RedirectResponse
    {
        Gate::authorize('moderate', $topic);

        $validated = $request->validate(['space_id' => ['required', 'integer', Rule::exists(CommunitySpace::class, 'id')]]);
        $topic->space_id = $validated['space_id'];
        $topic->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tópico movido.']);

        return back();
    }

    /**
     * Delete a topic: authors delete their own, admins any.
     */
    public function destroy(CommunityTopic $topic): RedirectResponse
    {
        Gate::authorize('delete', $topic);

        $topic->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tópico excluído.']);

        return to_route('community.index');
    }

    /**
     * Spaces in their order, with how many topics each has.
     *
     * @return Collection<int, CommunitySpace>
     */
    private function spaces(): Collection
    {
        return CommunitySpace::query()->withCount('topics')->orderBy('position')->orderBy('id')->get();
    }

    /**
     * @return array{name: string, is_team: bool}
     */
    private function author(User $user): array
    {
        return ['name' => $user->name, 'is_team' => $user->isAdmin()];
    }

    /**
     * @return array{id: int, body: string|null, body_html: string, author: array{name: string, is_team: bool}, created_at: string|null, edited: bool, is_solution: bool, can: array{update: bool, delete: bool, report: bool}}
     */
    private function reply(CommunityReply $reply, User $user, CommunityTopic $topic): array
    {
        return [
            'id' => $reply->id,
            'body' => $user->can('update', $reply) ? $reply->body : null,
            'body_html' => $reply->bodyHtml(),
            'author' => $this->author($reply->user),
            'created_at' => $reply->created_at?->toIso8601String(),
            'edited' => $reply->edited_at !== null,
            'is_solution' => $reply->id === $topic->solution_reply_id,
            'can' => [
                'update' => $user->can('update', $reply),
                'delete' => $user->can('delete', $reply),
                'report' => $user->can('report', $reply),
            ],
        ];
    }
}
