<?php

use App\Models\Comment;
use App\Models\CommunityMessage;
use App\Models\Course;
use App\Models\Module;
use App\Models\User;
use App\Notifications\CommunityMessageReplied;
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
    $reply = CommunityMessage::factory()->for(User::factory()->create(['name' => 'Bruno']))->create();
    $student->notify(new CommunityMessageReplied($reply));

    $this->actingAs($student)
        ->getJson(route('notifications.index'))
        ->assertOk()
        ->assertJsonPath('unread', 1)
        ->assertJsonPath('notifications.0.actor', 'Bruno')
        ->assertJsonPath('notifications.0.read', false);

    $this->actingAs($student)
        ->get(route('materials.index'))
        ->assertInertia(fn (Assert $page) => $page->where('unreadNotifications', 1));
});

test('opening a notification marks it read and goes to the reply in its channel', function () {
    $student = User::factory()->create();
    $reply = CommunityMessage::factory()->create();
    $student->notify(new CommunityMessageReplied($reply));
    $notification = $student->notifications()->sole();

    $this->actingAs(User::factory()->create())->get(route('notifications.show', $notification->id))->assertNotFound();

    $this->actingAs($student)
        ->get(route('notifications.show', $notification->id))
        ->assertRedirect(route('community.channels.show', [$reply->space->slug, 'mensagem' => $reply->id]));

    expect($notification->refresh()->read_at)->not->toBeNull();
});

test('all notifications are marked read at once', function () {
    $student = User::factory()->create();
    $student->notify(new CommunityMessageReplied(CommunityMessage::factory()->create()));
    $student->notify(new CommunityMessageReplied(CommunityMessage::factory()->create()));

    $this->actingAs($student)->postJson(route('notifications.read-all'))->assertExactJson(['unread' => 0]);

    expect($student->unreadNotifications()->count())->toBe(0);
});

test('the bell shows reply excerpts as plain text', function () {
    $student = User::factory()->create();
    $student->notify(new CommunityMessageReplied(CommunityMessage::factory()->create(['body' => 'Deixe o app **atualizado**.'])));

    $this->actingAs($student)
        ->getJson(route('notifications.index'))
        ->assertJsonPath('notifications.0.excerpt', 'Deixe o app atualizado.');
});

test('notifications load what they show when the reply comes without its relations', function () {
    Storage::fake();
    $student = User::factory()->create();
    $lesson = visibleLesson(Module::factory()->for(Course::factory()->published())->create());
    $comment = Comment::factory()->for($lesson)->create(['parent_id' => Comment::factory()->for($lesson)->create()->id]);

    $student->notify(new CommunityMessageReplied(CommunityMessage::query()->findOrFail(CommunityMessage::factory()->create()->id)));
    $student->notify(new LessonCommentReplied(Comment::query()->findOrFail($comment->id)));

    expect($student->notifications()->count())->toBe(2);
});

test('replies are also e-mailed to verified users who keep e-mails on', function () {
    Notification::fake();
    $reply = CommunityMessage::factory()->create(['body' => 'Veja o tutorial.']);
    $topicAuthor = User::factory()->create();
    $optedOut = User::factory()->create(['notify_by_email' => false]);
    $unverified = User::factory()->unverified()->create();

    $topicAuthor->notify(new CommunityMessageReplied($reply));
    $optedOut->notify(new CommunityMessageReplied($reply));
    $unverified->notify(new CommunityMessageReplied($reply));

    Notification::assertSentTo($topicAuthor, CommunityMessageReplied::class, function (CommunityMessageReplied $notification, array $channels) use ($topicAuthor, $reply) {
        $mail = $notification->toMail($topicAuthor);

        return $channels === ['database', 'mail']
            && $mail->actionUrl === route('community.channels.show', [$reply->space->slug, 'mensagem' => $reply->id])
            && str_contains(implode(' ', $mail->introLines), 'Veja o tutorial.');
    });
    Notification::assertSentTo($optedOut, CommunityMessageReplied::class, fn ($notification, array $channels) => $channels === ['database']);
    Notification::assertSentTo($unverified, CommunityMessageReplied::class, fn ($notification, array $channels) => $channels === ['database']);
});

test('students turn reply e-mails off and on in their settings', function () {
    $student = User::factory()->create();

    $this->actingAs($student)
        ->get(route('notification-settings.edit'))
        ->assertInertia(fn (Assert $page) => $page->component('settings/Notifications')->where('notifyByEmail', true));

    $this->actingAs($student)
        ->put(route('notification-settings.update'), ['notify_by_email' => '0'])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast.message', 'Preferência salva.');
    expect($student->refresh()->notify_by_email)->toBeFalse();

    $this->actingAs($student)->put(route('notification-settings.update'), ['notify_by_email' => '1']);
    expect($student->refresh()->notify_by_email)->toBeTrue();
});
