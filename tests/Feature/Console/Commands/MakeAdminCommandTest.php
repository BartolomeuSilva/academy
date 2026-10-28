<?php

use App\Enums\UserRole;
use App\Models\User;

test('it promotes an existing user to admin', function () {
    $user = User::factory()->create(['email' => 'aluno@example.com']);

    $this->artisan('academy:make-admin', ['email' => 'Aluno@Example.com'])
        ->expectsOutputToContain('aluno@example.com agora é administrador.')
        ->assertSuccessful();

    expect($user->fresh()->role)->toBe(UserRole::Admin);
});

test('it creates a verified admin when the user does not exist', function () {
    $this->artisan('academy:make-admin', ['email' => 'novo@example.com', '--name' => 'Bartolomeu'])
        ->expectsOutputToContain('Senha temporária')
        ->assertSuccessful();

    $user = User::query()->where('email', 'novo@example.com')->sole();

    expect($user->name)->toBe('Bartolomeu')
        ->and($user->role)->toBe(UserRole::Admin)
        ->and($user->hasVerifiedEmail())->toBeTrue();
});

test('it rejects an invalid email', function () {
    $this->artisan('academy:make-admin', ['email' => 'nao-e-email'])
        ->expectsOutputToContain('E-mail inválido')
        ->assertFailed();

    $this->assertDatabaseCount('users', 0);
});
