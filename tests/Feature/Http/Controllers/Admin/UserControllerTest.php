<?php

use App\Enums\UserRole;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('admins list users by name', function () {
    $admin = User::factory()->admin()->create(['name' => 'Carla']);
    $blocked = User::factory()->blocked()->unverified()->create(['name' => 'Ana']);

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/users/Index')
            ->has('users.data', 2)
            ->where('users.data.0.id', $blocked->id)
            ->where('users.data.0.role', 'student')
            ->where('users.data.0.blocked', true)
            ->where('users.data.0.verified', false)
            ->where('users.data.0.is_self', false)
            ->where('users.data.1.is_self', true)
            ->missing('users.data.0.password'));
});

test('users can be searched by name or e-mail ignoring case', function () {
    $admin = User::factory()->admin()->create(['name' => 'Admin', 'email' => 'admin@example.com']);
    $byName = User::factory()->create(['name' => 'Mariana Souza', 'email' => 'm@example.com']);
    $byEmail = User::factory()->create(['name' => 'Outra', 'email' => 'souza.r@example.com']);

    $this->actingAs($admin)
        ->get(route('admin.users.index', ['busca' => ' SOUZA ']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('users.data', 2)
            ->where('users.data.0.id', $byName->id)
            ->where('users.data.1.id', $byEmail->id)
            ->where('filters.search', 'SOUZA'));
});

test('students can not manage users', function () {
    $student = User::factory()->create();
    $other = User::factory()->create();

    $this->actingAs($student)->get(route('admin.users.index'))->assertForbidden();
    $this->actingAs($student)->put(route('admin.users.role.update', $other), ['role' => 'admin'])->assertForbidden();
    $this->actingAs($student)->post(route('admin.users.block.store', $other))->assertForbidden();

    expect($other->fresh())
        ->role->toBe(UserRole::Student)
        ->isBlocked()->toBeFalse();
});

test('admins promote and demote other users', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create(['name' => 'Bruno']);

    $this->actingAs($admin)
        ->put(route('admin.users.role.update', $user), ['role' => 'admin'])
        ->assertRedirect()
        ->assertInertiaFlash('toast.message', 'Bruno agora é administrador.');

    expect($user->fresh()->role)->toBe(UserRole::Admin);

    $this->actingAs($admin)
        ->put(route('admin.users.role.update', $user), ['role' => 'student'])
        ->assertInertiaFlash('toast.message', 'Bruno agora é aluno.');

    expect($user->fresh()->role)->toBe(UserRole::Student);
});

test('the role must be a known one', function () {
    $user = User::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.users.role.update', $user), ['role' => 'owner'])
        ->assertSessionHasErrors('role');

    expect($user->fresh()->role)->toBe(UserRole::Student);
});

test('admins can not demote or block themselves, so the last admin stays', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->put(route('admin.users.role.update', $admin), ['role' => 'student'])->assertForbidden();
    $this->actingAs($admin)->post(route('admin.users.block.store', $admin))->assertForbidden();

    expect($admin->fresh())
        ->role->toBe(UserRole::Admin)
        ->isBlocked()->toBeFalse();
});

test('admins block and unblock other users', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create(['name' => 'Bruno']);

    $this->actingAs($admin)
        ->post(route('admin.users.block.store', $user))
        ->assertRedirect()
        ->assertInertiaFlash('toast.message', 'Bruno foi bloqueado.');

    expect($user->fresh()->isBlocked())->toBeTrue();

    $this->actingAs($admin)
        ->delete(route('admin.users.block.destroy', $user))
        ->assertInertiaFlash('toast.message', 'Bruno foi desbloqueado.');

    expect($user->fresh()->isBlocked())->toBeFalse();
});
