<?php

use App\Http\Controllers\Settings\AvatarController;
use App\Jobs\DeleteStoragePaths;
use App\Models\CommunityMessage;
use App\Models\CommunitySpace;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake();
    Bus::fake([DeleteStoragePaths::class]);
});

test('a user uploads a profile photo, replacing the previous one', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('avatar.store'), ['avatar' => UploadedFile::fake()->image('eu.jpg', 200, 200)])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast.message', 'Foto atualizada.');

    $first = $user->refresh()->avatar_path;
    Storage::assertExists($first);

    $this->actingAs($user)->post(route('avatar.store'), ['avatar' => UploadedFile::fake()->image('novo.png', 200, 200)]);

    expect($user->refresh()->avatar_path)->not->toBe($first);
    Bus::assertDispatched(DeleteStoragePaths::class, fn (DeleteStoragePaths $job) => $job->paths === [$first]);
});

test('the photo must be a small enough image of at least 64 pixels', function (Closure $file) {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('avatar.store'), ['avatar' => $file()])
        ->assertSessionHasErrors('avatar');

    expect($user->refresh()->avatar_path)->toBeNull();
})->with([
    'not an image' => [fn () => UploadedFile::fake()->create('foto.pdf', 10)],
    'too large' => [fn () => UploadedFile::fake()->image('foto.jpg', 400, 400)->size(AvatarController::MAX_KILOBYTES + 1)],
    'too small' => [fn () => UploadedFile::fake()->image('foto.jpg', 32, 32)],
]);

test('a user removes their profile photo', function () {
    $user = User::factory()->create(['avatar_path' => 'avatars/eu.jpg']);

    $this->actingAs($user)->delete(route('avatar.destroy'))->assertInertiaFlash('toast.message', 'Foto removida.');

    expect($user->refresh()->avatar_path)->toBeNull();
    Bus::assertDispatched(DeleteStoragePaths::class, fn (DeleteStoragePaths $job) => $job->paths === ['avatars/eu.jpg']);
});

test('the photo is shared with the page and shown next to community messages', function () {
    $user = User::factory()->create(['avatar_path' => 'avatars/eu.jpg']);
    CommunityMessage::factory()->for($user)->for(CommunitySpace::query()->where('slug', 'geral')->sole(), 'space')->create();

    $this->actingAs($user)
        ->get(route('community.channels.show', 'geral'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.user.avatar', fn (?string $url) => str_contains((string) $url, 'avatars/eu.jpg'))
            ->missing('auth.user.avatar_path')
            ->where('messages.0.author.avatar', fn (?string $url) => str_contains((string) $url, 'avatars/eu.jpg')));
});

test('deleting the account removes the photo from storage', function () {
    $user = User::factory()->create(['avatar_path' => 'avatars/eu.jpg']);

    $user->delete();

    Bus::assertDispatched(DeleteStoragePaths::class, fn (DeleteStoragePaths $job) => $job->paths === ['avatars/eu.jpg']);
});
