<?php

use App\Http\Controllers\Community\TopicController;
use App\Models\CommunityReply;
use App\Models\CommunityTopic;
use App\Models\User;
use App\Notifications\CommunityReplyPosted;
use Illuminate\Support\Facades\Notification;

test('a reply bumps the topic and notifies its author', function () {
    Notification::fake();
    $topic = CommunityTopic::factory()->create(['last_activity_at' => now()->subDay()]);
    $student = User::factory()->create();

    $this->actingAs($student)
        ->post(route('community.topics.replies.store', $topic), ['body' => '  Funcionou aqui.  '])
        ->assertRedirect(route('community.topics.show', [$topic, 'page' => 1]));

    $reply = CommunityReply::query()->sole();
    expect($reply)->body->toBe('Funcionou aqui.')->user_id->toBe($student->id)
        ->and($topic->refresh()->last_activity_at->isToday())->toBeTrue();

    Notification::assertSentTo($topic->user, CommunityReplyPosted::class, fn (CommunityReplyPosted $notification) => $notification->reply->is($reply));
});

test('replying to your own topic does not notify you', function () {
    Notification::fake();
    $topic = CommunityTopic::factory()->create();

    $this->actingAs($topic->user)->post(route('community.topics.replies.store', $topic), ['body' => 'Resolvi sozinho.']);

    Notification::assertNothingSent();
});

test('after replying the student lands on the last page of replies', function () {
    $topic = CommunityTopic::factory()->create();
    CommunityReply::factory()->count(TopicController::REPLIES_PER_PAGE)->for($topic, 'topic')->create();

    $this->actingAs(User::factory()->create())
        ->post(route('community.topics.replies.store', $topic), ['body' => 'Mais uma.'])
        ->assertRedirect(route('community.topics.show', [$topic, 'page' => 2]));
});

test('a reply cannot be empty or too long', function () {
    $topic = CommunityTopic::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('community.topics.replies.store', $topic), ['body' => str_repeat('a', CommunityReply::MAX_BODY_LENGTH + 1)])
        ->assertSessionHasErrors('body');

    expect(CommunityReply::query()->count())->toBe(0);
});

test('only the author edits a reply', function () {
    $reply = CommunityReply::factory()->create(['body' => 'Antes']);

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('community.topics.replies.update', [$reply->topic, $reply]), ['body' => 'Depois'])
        ->assertForbidden();
    $this->actingAs($reply->user)
        ->put(route('community.topics.replies.update', [$reply->topic, $reply]), ['body' => 'Depois']);

    expect($reply->refresh())->body->toBe('Depois')->edited_at->not->toBeNull();
});

test('deleting the answer clears the topic solution', function () {
    $topic = CommunityTopic::factory()->create();
    $reply = CommunityReply::factory()->for($topic, 'topic')->create();
    $topic->forceFill(['solution_reply_id' => $reply->id])->save();

    $this->actingAs(User::factory()->create())
        ->delete(route('community.topics.replies.destroy', [$topic, $reply]))
        ->assertForbidden();
    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('community.topics.replies.destroy', [$topic, $reply]));

    expect($reply->refresh()->trashed())->toBeTrue()
        ->and($topic->refresh()->solution_reply_id)->toBeNull();
});

test('replies are only reached through their own topic', function () {
    $reply = CommunityReply::factory()->create();

    $this->actingAs($reply->user)
        ->delete(route('community.topics.replies.destroy', [CommunityTopic::factory()->create(), $reply]))
        ->assertNotFound();
});
