<?php

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonAttachment;
use App\Models\LessonProgress;
use App\Models\Module;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake();
});

test('the lesson page has the player, materials and neighbouring lessons', function () {
    $course = Course::factory()->published()->create();
    $first = Module::factory()->for($course)->create(['position' => 1]);
    $second = Module::factory()->for($course)->create(['position' => 2]);
    $previous = visibleLesson($first, ['position' => 1]);
    $lesson = visibleLesson($first, ['position' => 2]);
    Lesson::factory()->for($second)->create(['position' => 1]);
    $next = visibleLesson($second, ['position' => 2]);
    $attachment = LessonAttachment::factory()->for($lesson)->create();

    $this->actingAs(User::factory()->create())
        ->get(route('lessons.show', [$course->slug, $lesson->slug]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('lessons/Show')
            ->where('lesson.id', $lesson->id)
            ->where('video.playlist_url', route('videos.playlist', [$lesson->video_id, 'master']))
            ->where('attachments.0.url', route('attachments.show', $attachment))
            ->where('attachments.0.download_url', route('attachments.show', [$attachment, 'download' => 1]))
            ->where('previous_lesson.id', $previous->id)
            ->where('next_lesson.id', $next->id)
            ->where('progress.completed', false)
            ->where('progress.start_at_seconds', 0));
});

test('the video resumes from the saved position unless it was near the end', function (int $savedPosition, int $expectedStart) {
    $student = User::factory()->create();
    $lesson = visibleLesson(Module::factory()->for(Course::factory()->published())->create(), durationSeconds: 600);
    LessonProgress::factory()->for($student)->for($lesson)->create(['last_position_seconds' => $savedPosition]);

    $this->actingAs($student)
        ->get(route('lessons.show', [$lesson->course->slug, $lesson->slug]))
        ->assertInertia(fn (Assert $page) => $page->where('progress.start_at_seconds', $expectedStart));
})->with([
    'middle of the video' => [240, 240],
    'last seconds' => [590, 0],
]);

test('students get not found for lessons they can not watch', function (string $state) {
    $course = Course::factory()->published()->create();
    visibleLesson(Module::factory()->for($course)->create());
    $module = Module::factory()->for($course)->create();

    $lesson = match ($state) {
        'draft' => Lesson::factory()->for($module)->withVideo()->create(),
        'without video' => Lesson::factory()->for($module)->published()->create(),
        'draft course' => visibleLesson(Module::factory()->for(Course::factory())->create()),
    };

    $this->actingAs(User::factory()->create())
        ->get(route('lessons.show', [$lesson->course->slug, $lesson->slug]))
        ->assertNotFound();
})->with(['draft', 'without video', 'draft course']);

test('lessons are only found inside their own course', function () {
    $lesson = visibleLesson(Module::factory()->for(Course::factory()->published())->create());
    $otherCourse = Course::factory()->published()->create();
    visibleLesson(Module::factory()->for($otherCourse)->create());

    $this->actingAs(User::factory()->create())
        ->get(route('lessons.show', [$otherCourse->slug, $lesson->slug]))
        ->assertNotFound();
});

test('admins preview draft lessons without a video', function () {
    $lesson = Lesson::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('lessons.show', [$lesson->course->slug, $lesson->slug]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('video', null));
});
