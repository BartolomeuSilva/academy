<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Fortify\Features;
use Laravel\Passkeys\Contracts\PasskeyLoginResponse;
use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\Passkeys;

test('login screen can be rendered', function () {
    $response = $this->get(route('login'));

    $response->assertOk();
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('courses.index'));
});

test('blocked users can not authenticate', function () {
    $user = User::factory()->blocked()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors(['email' => 'Sua conta foi bloqueada. Entre em contato com um administrador.']);
});

test('blocked users can not log in with a passkey', function () {
    $passkey = (new Passkey)->setRelation('user', User::factory()->blocked()->create());
    $activePasskey = (new Passkey)->setRelation('user', User::factory()->create());

    expect(Passkeys::allowsLogin(Request::create('/'), $passkey))->toBeFalse()
        ->and(Passkeys::allowsLogin(Request::create('/'), $activePasskey))->toBeTrue();
});

test('passkey login response redirects to the catalog', function () {
    $user = User::factory()->create();

    $request = Request::create(route('login', absolute: false), 'GET', server: [
        'HTTP_ACCEPT' => 'application/json',
    ]);
    $request->setLaravelSession($this->app['session.store']);
    $request->setUserResolver(fn () => $user);

    $jsonResponse = app(PasskeyLoginResponse::class)->toResponse($request);

    expect($jsonResponse->getData()->redirect)->toBe(route('courses.index'));
});

test('users with two factor enabled are redirected to two factor challenge', function () {
    if (! Features::canManageTwoFactorAuthentication()) {
        $this->markTestSkipped('Two-factor authentication is not enabled.');
    }

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->withTwoFactor()->create();

    $response = $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('two-factor.login'));
    $response->assertSessionHas('login.id', $user->id);
    $this->assertGuest();
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('logout'));

    $this->assertGuest();
    $response->assertRedirect(route('home'));
});

test('users are rate limited', function () {
    $user = User::factory()->create();

    RateLimiter::increment(md5('login'.implode('|', [$user->email, '127.0.0.1'])), amount: 5);

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertTooManyRequests();
});
