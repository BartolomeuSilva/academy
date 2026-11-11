<?php

namespace App\Policies;

use App\Models\CommunityMessage;
use App\Models\CommunitySpace;
use App\Models\User;

class CommunityMessagePolicy
{
    /**
     * Everyone posts in a channel, except in team-only ones such as "Avisos".
     */
    public function create(User $user, CommunitySpace $space): bool
    {
        return ! $space->staff_only || $user->isAdmin();
    }

    /**
     * Only the author edits a message.
     */
    public function update(User $user, CommunityMessage $message): bool
    {
        return $message->user_id === $user->id;
    }

    /**
     * The author deletes their own message; admins delete any message.
     */
    public function delete(User $user, CommunityMessage $message): bool
    {
        return $user->isAdmin() || $message->user_id === $user->id;
    }

    /**
     * Anyone but the author reports a message.
     */
    public function report(User $user, CommunityMessage $message): bool
    {
        return $message->user_id !== $user->id;
    }
}
