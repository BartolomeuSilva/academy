<?php

use App\Models\User;

test('blocked users are logged out and sent to login', function () {
    $user = User::factory()->blocked()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('status', 'Sua conta foi bloqueada. Entre em contato com um administrador.');

    $this->assertGuest();
});

test('active users keep their session', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk();

    $this->assertAuthenticated();
});
