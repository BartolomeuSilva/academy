<?php

namespace App\Http\Controllers\Admin;

use App\Actions\UpdateUserAccess;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class UserBlockController extends Controller
{
    /**
     * Block a user; they are logged out on their next request.
     */
    public function store(Request $request, User $user, UpdateUserAccess $updateUserAccess): RedirectResponse
    {
        Gate::authorize('block', $user);

        $updateUserAccess->handle($request->user(), $user, ['blocked_at' => now()]);

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$user->name} foi bloqueado."]);

        return back();
    }

    /**
     * Let a blocked user log in again.
     */
    public function destroy(Request $request, User $user, UpdateUserAccess $updateUserAccess): RedirectResponse
    {
        Gate::authorize('block', $user);

        $updateUserAccess->handle($request->user(), $user, ['blocked_at' => null]);

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$user->name} foi desbloqueado."]);

        return back();
    }
}
