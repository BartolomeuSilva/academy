<?php

namespace App\Actions;

use App\Enums\UserRole;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class UpdateUserAccess
{
    /**
     * Change a user's role or blocked state on behalf of an admin.
     *
     * Active admins are locked first, so two admins demoting or blocking
     * each other at the same time can never leave the platform without one.
     *
     * @param  array{role?: UserRole, blocked_at?: DateTimeInterface|null}  $attributes
     *
     * @throws AuthorizationException
     */
    public function handle(User $admin, User $user, array $attributes): void
    {
        DB::transaction(function () use ($admin, $user, $attributes): void {
            $activeAdminIds = User::query()
                ->where('role', UserRole::Admin)
                ->whereNull('blocked_at')
                ->lockForUpdate()
                ->pluck('id');

            if (! $activeAdminIds->contains($admin->id)) {
                throw new AuthorizationException;
            }

            $user->forceFill($attributes)->save();
        });
    }
}
