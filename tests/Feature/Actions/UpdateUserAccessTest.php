<?php

use App\Actions\UpdateUserAccess;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

test('an admin who lost access meanwhile can not change other users', function (array $lostAccess) {
    $admin = User::factory()->admin()->create();
    $other = User::factory()->admin()->create();

    User::query()->whereKey($admin->id)->update($lostAccess);

    expect(fn () => (new UpdateUserAccess)->handle($admin, $other, ['role' => UserRole::Student]))
        ->toThrow(AuthorizationException::class);

    expect($other->fresh()->role)->toBe(UserRole::Admin);
})->with([
    'demoted' => [['role' => UserRole::Student]],
    'blocked' => [['blocked_at' => now()]],
]);
