<?php

namespace App\Policies;

use App\Models\CommunitySpace;
use App\Models\CommunityTopic;
use App\Models\User;

class CommunityTopicPolicy
{
    /**
     * Everyone starts topics, except in team-only spaces such as "Avisos".
     */
    public function create(User $user, CommunitySpace $space): bool
    {
        return ! $space->staff_only || $user->isAdmin();
    }

    /**
     * Only the author edits a topic.
     */
    public function update(User $user, CommunityTopic $topic): bool
    {
        return $topic->user_id === $user->id;
    }

    /**
     * The author deletes their own topic; admins delete any topic.
     */
    public function delete(User $user, CommunityTopic $topic): bool
    {
        return $user->isAdmin() || $topic->user_id === $user->id;
    }

    /**
     * The author or the team marks which reply solved the topic.
     */
    public function solve(User $user, CommunityTopic $topic): bool
    {
        return $user->isAdmin() || $topic->user_id === $user->id;
    }

    /**
     * Only the team pins topics or moves them to another space.
     */
    public function moderate(User $user, CommunityTopic $topic): bool
    {
        return $user->isAdmin();
    }

    /**
     * Anyone but the author reports a topic.
     */
    public function report(User $user, CommunityTopic $topic): bool
    {
        return $topic->user_id !== $user->id;
    }
}
