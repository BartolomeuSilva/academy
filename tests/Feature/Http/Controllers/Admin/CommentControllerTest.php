<?php

use App\Models\Comment;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('admins see the most recent comments of every course', function () {
    $older = Comment::factory()->create();
    $newer = Comment::factory()->replyTo($older)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.comments.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/comments/Index')
            ->where('comments.data.0.id', $newer->id)
            ->where('comments.data.0.is_reply', true)
            ->where('comments.data.1.id', $older->id)
            ->where('comments.data.1.course.title', $older->lesson->course->title));
});

test('comments can be filtered by course', function () {
    $course = Course::factory()->create();
    $inCourse = Comment::factory()->for(Lesson::factory()->for(Module::factory()->for($course)))->create();
    Comment::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.comments.index', ['curso' => $course->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('comments.data', 1)
            ->where('comments.data.0.id', $inCourse->id)
            ->where('filters.course_id', $course->id));
});

test('deleted comments are not listed', function () {
    Comment::factory()->create()->delete();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.comments.index'))
        ->assertInertia(fn (Assert $page) => $page->has('comments.data', 0));
});

test('moderating a root comment removes its replies', function () {
    $root = Comment::factory()->create();
    Comment::factory()->replyTo($root)->count(2)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('admin.comments.destroy', $root))
        ->assertRedirect()
        ->assertInertiaFlash('toast.message', 'Comentário excluído.');

    expect(Comment::query()->count())->toBe(0);
});
