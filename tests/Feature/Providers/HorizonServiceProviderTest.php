<?php

use App\Models\User;
use Illuminate\Support\Facades\Gate;

test('only admins may view the horizon dashboard', function (bool $isAdmin, bool $allowed) {
    $user = $isAdmin ? User::factory()->admin()->create() : User::factory()->create();

    expect(Gate::forUser($user)->allows('viewHorizon'))->toBe($allowed);
})->with([
    'admin' => [true, true],
    'student' => [false, false],
]);

test('guests may not view the horizon dashboard', function () {
    expect(Gate::allows('viewHorizon'))->toBeFalse();
});
