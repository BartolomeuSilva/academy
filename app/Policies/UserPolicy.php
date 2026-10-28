<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Admins change the role of other users, never their own.
     */
    public function updateRole(User $user, User $model): bool
    {
        return $user->isAdmin() && $user->isNot($model);
    }

    /**
     * Admins block and unblock other users, never themselves.
     */
    public function block(User $user, User $model): bool
    {
        return $user->isAdmin() && $user->isNot($model);
    }
}
