<?php

use App\Enums\UserRole;
use App\Models\User;

test('an admin searches, promotes and blocks users', function () {
    $admin = User::factory()->admin()->create(['name' => 'Admin Principal']);
    $user = User::factory()->create(['name' => 'Bruno Lima', 'email' => 'bruno@example.com']);
    User::factory()->create(['name' => 'Carla Dias']);
    $this->actingAs($admin);

    visit('/admin/usuarios')
        ->assertSee('Carla Dias')
        ->fill('@user-search-input', 'bruno')
        ->assertDontSee('Carla Dias')
        ->assertSee('bruno@example.com')
        ->click('@toggle-role-button')
        ->click('@confirm-dialog-button')
        ->assertSee('Bruno Lima agora é administrador.')
        ->click('@toggle-block-button')
        ->click('@confirm-dialog-button')
        ->assertSee('Bruno Lima foi bloqueado.')
        ->assertSee('Bloqueado')
        ->assertNoJavaScriptErrors();

    expect($user->fresh())
        ->role->toBe(UserRole::Admin)
        ->isBlocked()->toBeTrue();
});

test('the users page has no accessibility issues', function () {
    User::factory()->blocked()->unverified()->create();
    $this->actingAs(User::factory()->admin()->create());

    visit('/admin/usuarios')->assertSee('(você)')->assertNoSmoke()->assertNoAccessibilityIssues();
    visit('/admin/usuarios')->inDarkMode()->assertNoAccessibilityIssues();
});
