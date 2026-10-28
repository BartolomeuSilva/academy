<?php

namespace App\Actions;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeleteComment
{
    /**
     * Soft delete the comment.
     *
     * When a moderator removes someone else's root comment its replies go with it; when authors remove their own
     * root comment the replies stay and the root is shown as "comentário removido".
     */
    public function handle(Comment $comment, User $deletedBy): void
    {
        DB::transaction(function () use ($comment, $deletedBy) {
            if ($comment->isRoot() && $comment->user_id !== $deletedBy->id) {
                $comment->replies()->delete();
            }

            $comment->delete();
        });
    }
}
