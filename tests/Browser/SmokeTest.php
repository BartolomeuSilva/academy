<?php

use App\Models\User;

test('public pages render without errors in light and dark mode', function () {
    $pages = visit(['/', '/login', '/register']);

    $pages->assertNoSmoke()->assertNoAccessibilityIssues();

    visit('/')->inDarkMode()->assertNoSmoke();
});

test('a visitor can register and reach the verification notice', function () {
    visit('/register')
        ->fill('name', 'Maria Souza')
        ->fill('email', 'maria@example.com')
        ->fill('password', 'senha-forte-123')
        ->fill('password_confirmation', 'senha-forte-123')
        ->click('@register-user-button')
        ->assertSee('Verifique seu e-mail');

    expect(User::query()->where('email', 'maria@example.com')->exists())->toBeTrue();
});

test('a student can log in and see the catalog', function () {
    $user = User::factory()->create(['password' => 'senha-forte-123']);

    visit('/login')
        ->fill('email', $user->email)
        ->fill('password', 'senha-forte-123')
        ->click('@login-button')
        ->assertPathIs('/cursos')
        ->assertSee('Nenhum curso disponível ainda')
        ->assertNoJavaScriptErrors();
});
