<?php

use App\Models\Comment;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake();
});

function commentableLesson(): Lesson
{
    return visibleLesson(Module::factory()->for(Course::factory()->published())->create());
}

test('students comment on a lesson', function () {
    $student = User::factory()->create();
    $lesson = commentableLesson();

    $this->actingAs($student)
        ->from(route('lessons.show', [$lesson->course->slug, $lesson->slug]))
        ->post(route('lessons.comments.store', $lesson), ['body' => "  Ótima aula!\nObrigado.  "])
        ->assertRedirect(route('lessons.show', [$lesson->course->slug, $lesson->slug]));

    expect(Comment::query()->sole())
        ->body->toBe("Ótima aula!\nObrigado.")
        ->user_id->toBe($student->id)
        ->lesson_id->toBe($lesson->id)
        ->parent_id->toBeNull();
});

test('students reply to a root comment', function () {
    $lesson = commentableLesson();
    $root = Comment::factory()->for($lesson)->create();

    $this->actingAs(User::factory()->create())
        ->post(route('lessons.comments.store', $lesson), ['body' => 'Também tenho essa dúvida.', 'parent_id' => $root->id])
        ->assertSessionHasNoErrors();

    expect($root->replies()->sole()->body)->toBe('Também tenho essa dúvida.');
});

test('replies only go to visible root comments of the same lesson', function (string $case) {
    $lesson = commentableLesson();
    $root = Comment::factory()->for($lesson)->create();

    $parent = match ($case) {
        'a reply' => Comment::factory()->replyTo($root)->create(),
        'another lesson' => Comment::factory()->for(commentableLesson())->create(),
        'a deleted comment' => tap(Comment::factory()->for($lesson)->create())->delete(),
    };

    $this->actingAs(User::factory()->create())
        ->post(route('lessons.comments.store', $lesson), ['body' => 'Resposta', 'parent_id' => $parent->id])
        ->assertSessionHasErrors(['parent_id' => 'Só é possível responder a um comentário principal desta aula.']);
})->with(['a reply', 'another lesson', 'a deleted comment']);

test('comments must have between 1 and 2000 characters', function (string $body) {
    $this->actingAs(User::factory()->create())
        ->post(route('lessons.comments.store', commentableLesson()), ['body' => $body])
        ->assertSessionHasErrors('body');
})->with([
    'empty' => '',
    'only spaces' => '   ',
    'too long' => str_repeat('a', 2001),
]);

test('students can not comment on lessons they can not watch', function () {
    $draft = Lesson::factory()->for(Module::factory()->for(Course::factory()->published()))->withVideo()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('lessons.comments.store', $draft), ['body' => 'Olá'])
        ->assertNotFound();
});

test('students may post at most 10 comments per minute', function () {
    $student = User::factory()->create();
    $lesson = commentableLesson();

    foreach (range(1, 10) as $number) {
        $this->actingAs($student)->post(route('lessons.comments.store', $lesson), ['body' => "Comentário {$number}"])->assertSessionHasNoErrors();
    }

    $this->actingAs($student)
        ->post(route('lessons.comments.store', $lesson), ['body' => 'Mais um'])
        ->assertSessionHasErrors(['body' => 'Você enviou muitos comentários em pouco tempo. Aguarde um minuto e tente novamente.']);

    expect(Comment::query()->count())->toBe(10);

    $this->travel(61)->seconds();
    $this->actingAs($student)->post(route('lessons.comments.store', $lesson), ['body' => 'Depois de um minuto'])->assertSessionHasNoErrors();
});

test('authors edit their comments and the change is marked as edited', function () {
    $comment = Comment::factory()->for(commentableLesson())->create(['body' => 'Antes']);

    $this->actingAs($comment->user)
        ->patch(route('lessons.comments.update', [$comment->lesson, $comment]), ['body' => 'Depois'])
        ->assertSessionHasNoErrors();

    expect($comment->refresh())
        ->body->toBe('Depois')
        ->edited_at->not->toBeNull();
});

test('saving the same text does not mark the comment as edited', function () {
    $comment = Comment::factory()->for(commentableLesson())->create(['body' => 'Igual']);

    $this->actingAs($comment->user)->patch(route('lessons.comments.update', [$comment->lesson, $comment]), ['body' => 'Igual ']);

    expect($comment->refresh()->edited_at)->toBeNull();
});

test('nobody else edits a comment, not even admins', function () {
    $comment = Comment::factory()->for(commentableLesson())->create();

    foreach ([User::factory()->create(), User::factory()->admin()->create()] as $user) {
        $this->actingAs($user)
            ->patch(route('lessons.comments.update', [$comment->lesson, $comment]), ['body' => 'Alterado'])
            ->assertForbidden();
    }
});

test('comments are only found inside their own lesson', function () {
    $comment = Comment::factory()->for(commentableLesson())->create();

    $this->actingAs($comment->user)
        ->delete(route('lessons.comments.destroy', [commentableLesson(), $comment]))
        ->assertNotFound();
});

test('authors delete their own replies and root comments without replies', function () {
    $root = Comment::factory()->for(commentableLesson())->create();
    $reply = Comment::factory()->replyTo($root)->create();

    $this->actingAs($reply->user)->delete(route('lessons.comments.destroy', [$root->lesson, $reply]))->assertRedirect();
    $this->actingAs($root->user)->delete(route('lessons.comments.destroy', [$root->lesson, $root]))->assertRedirect();

    expect(Comment::query()->count())->toBe(0);
});

test('a root comment removed by its author keeps its replies', function () {
    $root = Comment::factory()->for(commentableLesson())->create();
    $reply = Comment::factory()->replyTo($root)->create();

    $this->actingAs($root->user)->delete(route('lessons.comments.destroy', [$root->lesson, $root]));

    expect($root->refresh()->trashed())->toBeTrue()
        ->and($reply->refresh()->trashed())->toBeFalse();
});

test('admins delete any comment and a removed root takes its replies', function () {
    $root = Comment::factory()->for(commentableLesson())->create();
    $reply = Comment::factory()->replyTo($root)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('lessons.comments.destroy', [$root->lesson, $root]))
        ->assertRedirect();

    expect(Comment::query()->count())->toBe(0)
        ->and($reply->refresh()->trashed())->toBeTrue();
});

test('students can not delete comments of other students', function () {
    $comment = Comment::factory()->for(commentableLesson())->create();

    $this->actingAs(User::factory()->create())
        ->delete(route('lessons.comments.destroy', [$comment->lesson, $comment]))
        ->assertForbidden();
});

test('the lesson page lists the newest comments first with replies oldest first', function () {
    $viewer = User::factory()->create();
    $lesson = commentableLesson();
    $older = Comment::factory()->for($lesson)->create(['body' => 'Mais antigo']);
    $newer = Comment::factory()->for($lesson)->for($viewer)->create(['body' => 'Mais novo']);
    Comment::factory()->replyTo($older)->create(['body' => 'Primeira resposta']);
    Comment::factory()->replyTo($older)->create(['body' => 'Segunda resposta', 'edited_at' => now()]);

    $this->actingAs($viewer)
        ->get(route('lessons.show', [$lesson->course->slug, $lesson->slug]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('comments.total', 4)
            ->where('comments.has_more', false)
            ->where('comments.data.0.id', $newer->id)
            ->where('comments.data.0.can_update', true)
            ->where('comments.data.0.can_delete', true)
            ->where('comments.data.1.id', $older->id)
            ->where('comments.data.1.can_update', false)
            ->where('comments.data.1.can_delete', false)
            ->where('comments.data.1.replies.0.body', 'Primeira resposta')
            ->where('comments.data.1.replies.1.body', 'Segunda resposta')
            ->where('comments.data.1.replies.1.edited', true));
});

test('deleted root comments stay as a placeholder only while they have replies', function () {
    $lesson = commentableLesson();
    $withReplies = Comment::factory()->for($lesson)->create(['body' => 'Segredo']);
    Comment::factory()->replyTo($withReplies)->create();
    $withoutReplies = Comment::factory()->for($lesson)->create();
    $withReplies->delete();
    $withoutReplies->delete();

    $this->actingAs(User::factory()->create())
        ->get(route('lessons.show', [$lesson->course->slug, $lesson->slug]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('comments.data', 1)
            ->where('comments.data.0.deleted', true)
            ->where('comments.data.0.body', null)
            ->where('comments.data.0.author', null)
            ->has('comments.data.0.replies', 1));
});

test('more root comments load on request', function () {
    $lesson = commentableLesson();
    Comment::factory()->for($lesson)->count(12)->create();
    $url = route('lessons.show', [$lesson->course->slug, $lesson->slug]);
    $student = User::factory()->create();

    $this->actingAs($student)->get($url)
        ->assertInertia(fn (Assert $page) => $page->has('comments.data', 10)->where('comments.has_more', true));

    $this->actingAs($student)->get("{$url}?comentarios=20")
        ->assertInertia(fn (Assert $page) => $page->has('comments.data', 12)->where('comments.has_more', false));
});
