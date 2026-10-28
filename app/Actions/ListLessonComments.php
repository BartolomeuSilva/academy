<?php

namespace App\Actions;

use App\Models\Comment;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @phpstan-type CommentData array{id: int, body: string|null, author: string|null, created_at: string|null, edited: bool, deleted: bool, can_update: bool, can_delete: bool}
 * @phpstan-type RootCommentData array{id: int, body: string|null, author: string|null, created_at: string|null, edited: bool, deleted: bool, can_update: bool, can_delete: bool, replies: list<CommentData>}
 */
class ListLessonComments
{
    /**
     * Get the newest root comments of the lesson with their replies, oldest reply first.
     *
     * Deleted root comments only remain, as a placeholder, while they still have replies.
     *
     * @return array{data: list<RootCommentData>, has_more: bool, total: int}
     */
    public function handle(Lesson $lesson, User $viewer, int $limit): array
    {
        $roots = $lesson->comments()
            ->withTrashed()
            ->whereNull('parent_id')
            ->where(fn (Builder $query) => $query->whereNull('deleted_at')->orWhereHas('replies'))
            ->with([
                'user:id,name',
                'replies' => fn (HasMany $replies) => $replies->oldest('id')->with('user:id,name'),
            ])
            ->latest('id')
            ->limit($limit + 1)
            ->get();

        return [
            'data' => array_values($roots->take($limit)->map(fn (Comment $root) => [
                ...$this->present($root, $viewer),
                'replies' => array_values($root->replies->map(fn (Comment $reply) => $this->present($reply, $viewer))->all()),
            ])->all()),
            'has_more' => $roots->count() > $limit,
            'total' => $lesson->comments()->count(),
        ];
    }

    /**
     * @return CommentData
     */
    private function present(Comment $comment, User $viewer): array
    {
        $deleted = $comment->trashed();

        return [
            'id' => $comment->id,
            'body' => $deleted ? null : $comment->body,
            'author' => $deleted ? null : $comment->user->name,
            'created_at' => $comment->created_at?->toIso8601String(),
            'edited' => $comment->edited_at !== null,
            'deleted' => $deleted,
            'can_update' => ! $deleted && $viewer->can('update', $comment),
            'can_delete' => ! $deleted && $viewer->can('delete', $comment),
        ];
    }
}
