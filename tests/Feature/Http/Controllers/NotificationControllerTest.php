<?php

use App\Models\Comment;
use App\Models\CommunityReply;
use App\Models\Course;
use App\Models\Module;
use App\Models\User;
use App\Notifications\CommunityReplyPosted;
use App\Notifications\LessonCommentReplied;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

test('replying to a lesson comment notifies its author, but not when replying to yourself', function () {
    Storage::fake();
    Notification::fake();
    $lesson = visibleLesson(Module::factory()->for(Course::factory()->published())->create());
    $question = Comment::factory()->for($lesson)->create();

    $this->actingAs($question->user)->post(route('lessons.comments.store', $lesson), ['body' => 'Complemento', 'parent_id' => $question->id]);
    Notification::assertNothingSent();

    $this->actingAs(User::factory()->create())->post(route('lessons.comments.store', $lesson), ['body' => 'Está nos materiais.', 'parent_id' => $question->id]);
    Notification::assertSentTo($question->user, LessonCommentReplied::class);
});

test('the bell lists the latest notifications and the unread count is shared', function () {
    $student = User::factory()->create();
    $reply = CommunityReply::factory()->for(User::factory()->create(['name' => 'Bruno']))->create();
    $student->notify(new CommunityReplyPosted($reply));

    $this->actingAs($student)
        ->getJson(route('notifications.index'))
        ->assertOk()
        ->assertJsonPath('unread', 1)
        ->assertJsonPath('notifications.0.actor', 'Bruno')
        ->assertJsonPath('notifications.0.read', false);

    $this->actingAs($student)
        ->get(route('community.index'))
        ->assertInertia(fn (Assert $page) => $page->where('unreadNotifications', 1));
});

test('opening a notification marks it read and goes to the reply', function () {
    $student = User::factory()->create();
    $reply = CommunityReply::factory()->create();
    $student->notify(new CommunityReplyPosted($reply));
    $notification = $student->notifications()->sole();

    $this->actingAs(User::factory()->create())->get(route('notifications.show', $notification->id))->assertNotFound();

    $this->actingAs($student)
        ->get(route('notifications.show', $notification->id))
        ->assertRedirect(route('community.topics.show', $reply->topic_id).'#resposta-'.$reply->id);

    expect($notification->refresh()->read_at)->not->toBeNull();
});

test('all notifications are marked read at once', function () {
    $student = User::factory()->create();
    $student->notify(new CommunityReplyPosted(CommunityReply::factory()->create()));
    $student->notify(new CommunityReplyPosted(CommunityReply::factory()->create()));

    $this->actingAs($student)->postJson(route('notifications.read-all'))->assertExactJson(['unread' => 0]);

    expect($student->unreadNotifications()->count())->toBe(0);
});

test('the bell shows reply excerpts as plain text', function () {
    $student = User::factory()->create();
    $student->notify(new CommunityReplyPosted(CommunityReply::factory()->create(['body' => 'Deixe o app **atualizado**.'])));

    $this->actingAs($student)
        ->getJson(route('notifications.index'))
        ->assertJsonPath('notifications.0.excerpt', 'Deixe o app atualizado.');
});

test('notifications load what they show when the reply comes without its relations', function () {
    Storage::fake();
    $student = User::factory()->create();
    $lesson = visibleLesson(Module::factory()->for(Course::factory()->published())->create());
    $comment = Comment::factory()->for($lesson)->create(['parent_id' => Comment::factory()->for($lesson)->create()->id]);

    $student->notify(new CommunityReplyPosted(CommunityReply::query()->findOrFail(CommunityReply::factory()->create()->id)));
    $student->notify(new LessonCommentReplied(Comment::query()->findOrFail($comment->id)));

    expect($student->notifications()->count())->toBe(2);
});
