<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::middleware(['web', 'auth', 'admin'])->get('/_test/admin', fn () => 'ok');
});

test('admins can access admin routes', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get('/_test/admin')
        ->assertOk();
});

test('students are forbidden from admin routes', function () {
    $this->actingAs(User::factory()->create())
        ->get('/_test/admin')
        ->assertForbidden();
});

test('guests are redirected to login from admin routes', function () {
    $this->get('/_test/admin')->assertRedirect(route('login'));
});
