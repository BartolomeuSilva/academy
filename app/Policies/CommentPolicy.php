<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;

class CommentPolicy
{
    /**
     * Only the author edits a comment.
     */
    public function update(User $user, Comment $comment): bool
    {
        return $comment->user_id === $user->id;
    }

    /**
     * The author deletes their own comment; admins delete any comment.
     */
    public function delete(User $user, Comment $comment): bool
    {
        return $user->isAdmin() || $comment->user_id === $user->id;
    }
}
