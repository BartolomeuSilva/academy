<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * List users, optionally searching by name or e-mail.
     */
    public function index(Request $request): Response
    {
        $search = $request->string('busca')->trim()->toString();

        $users = User::query()
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->whereLike('name', "%{$search}%")
                ->orWhereLike('email', "%{$search}%")))
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(20, ['id', 'name', 'email', 'email_verified_at', 'role', 'blocked_at', 'created_at'])
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'blocked' => $user->isBlocked(),
                'verified' => $user->email_verified_at !== null,
                'created_at' => $user->created_at?->toIso8601String(),
                'is_self' => $user->is($request->user()),
            ]);

        return Inertia::render('admin/users/Index', [
            'users' => $users,
            'filters' => ['search' => $search],
        ]);
    }
}
