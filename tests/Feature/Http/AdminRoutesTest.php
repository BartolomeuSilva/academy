<?php

use App\Models\User;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;

/**
 * Every admin route with placeholder parameters (or the first allowed value of a
 * constrained one), so new routes are covered automatically.
 *
 * @return array<string, array{string, string}>
 */
function adminRoutes(): array
{
    return collect(RouteFacade::getRoutes()->getRoutes())
        ->filter(fn (Route $route) => str_starts_with((string) $route->getName(), 'admin.'))
        ->mapWithKeys(fn (Route $route) => [
            $route->getName() => [
                collect($route->methods())->reject(fn (string $method) => $method === 'HEAD')->first(),
                '/'.preg_replace_callback(
                    '/\{([^}?]+)\??\}/',
                    fn (array $match) => isset($route->wheres[$match[1]]) ? explode('|', $route->wheres[$match[1]])[0] : '1',
                    $route->uri(),
                ),
            ],
        ])
        ->all();
}

test('students are forbidden from every admin route', function () {
    $student = User::factory()->create();

    expect(adminRoutes())->not->toBeEmpty();

    foreach (adminRoutes() as $name => [$method, $uri]) {
        $status = $this->actingAs($student)->call($method, $uri)->getStatusCode();

        expect($status)->toBe(403, "{$name} retornou {$status}");
    }
});

test('guests are redirected to login from every admin route', function () {
    foreach (adminRoutes() as $name => [$method, $uri]) {
        $response = $this->call($method, $uri);

        expect($response->headers->get('Location'))->toBe(route('login'), "{$name} não redirecionou para o login");
    }
});
