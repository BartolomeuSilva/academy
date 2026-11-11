<?php

namespace App\Policies;

use App\Models\CommunityReply;
use App\Models\User;

class CommunityReplyPolicy
{
    /**
     * Only the author edits a reply.
     */
    public function update(User $user, CommunityReply $reply): bool
    {
        return $reply->user_id === $user->id;
    }

    /**
     * The author deletes their own reply; admins delete any reply.
     */
    public function delete(User $user, CommunityReply $reply): bool
    {
        return $user->isAdmin() || $reply->user_id === $user->id;
    }

    /**
     * Anyone but the author reports a reply.
     */
    public function report(User $user, CommunityReply $reply): bool
    {
        return $reply->user_id !== $user->id;
    }
}
