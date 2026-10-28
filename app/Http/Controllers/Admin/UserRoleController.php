<?php

namespace App\Http\Controllers\Admin;

use App\Actions\UpdateUserAccess;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRoleRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class UserRoleController extends Controller
{
    /**
     * Promote a user to admin or demote them to student.
     */
    public function update(UpdateUserRoleRequest $request, User $user, UpdateUserAccess $updateUserAccess): RedirectResponse
    {
        $role = $request->role();

        $updateUserAccess->handle($request->user(), $user, ['role' => $role]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $role === UserRole::Admin
                ? "{$user->name} agora é administrador."
                : "{$user->name} agora é aluno.",
        ]);

        return back();
    }
}
