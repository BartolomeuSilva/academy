<?php

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Module;
use App\Models\User;

function progressLesson(): Lesson
{
    return visibleLesson(Module::factory()->for(Course::factory()->published())->create(), durationSeconds: 600);
}

test('saves the playback position', function () {
    $student = User::factory()->create();
    $lesson = progressLesson();

    $this->actingAs($student)
        ->putJson(route('lessons.progress.update', $lesson), ['position_seconds' => 120])
        ->assertOk()
        ->assertExactJson(['last_position_seconds' => 120, 'completed' => false]);

    $this->actingAs($student)->putJson(route('lessons.progress.update', $lesson), ['position_seconds' => 180]);

    expect(LessonProgress::query()->whereBelongsTo($student)->whereBelongsTo($lesson)->sole())
        ->last_position_seconds->toBe(180)
        ->completed_at->toBeNull();
});

test('completes the lesson once 90% of the video was watched', function () {
    $student = User::factory()->create();
    $lesson = progressLesson();

    $this->actingAs($student)->putJson(route('lessons.progress.update', $lesson), ['position_seconds' => 539])
        ->assertJsonPath('completed', false);
    $this->actingAs($student)->putJson(route('lessons.progress.update', $lesson), ['position_seconds' => 540])
        ->assertJsonPath('completed', true);
});

test('positions past the end of the video are clamped to its duration', function () {
    $lesson = progressLesson();

    $this->actingAs(User::factory()->create())
        ->putJson(route('lessons.progress.update', $lesson), ['position_seconds' => 5000])
        ->assertJsonPath('last_position_seconds', 600);
});

test('students mark and unmark a lesson as completed', function () {
    $student = User::factory()->create();
    $lesson = progressLesson();

    $this->actingAs($student)->putJson(route('lessons.progress.update', $lesson), ['completed' => true])
        ->assertJsonPath('completed', true);
    $this->actingAs($student)->putJson(route('lessons.progress.update', $lesson), ['completed' => false])
        ->assertJsonPath('completed', false);
});

test('an unmarked lesson does not complete again while playback stays past 90%', function () {
    $student = User::factory()->create();
    $lesson = progressLesson();
    LessonProgress::factory()->completed()->for($student)->for($lesson)->create(['last_position_seconds' => 560]);

    $this->actingAs($student)->putJson(route('lessons.progress.update', $lesson), ['completed' => false]);

    $this->actingAs($student)->putJson(route('lessons.progress.update', $lesson), ['position_seconds' => 580])
        ->assertJsonPath('completed', false);
});

test('progress is kept per student', function () {
    $lesson = progressLesson();
    $other = LessonProgress::factory()->for($lesson)->create(['last_position_seconds' => 30]);

    $this->actingAs(User::factory()->create())
        ->putJson(route('lessons.progress.update', $lesson), ['position_seconds' => 300]);

    expect($other->refresh()->last_position_seconds)->toBe(30);
});

test('progress needs a position or a completion flag', function (array $payload, string $field) {
    $this->actingAs(User::factory()->create())
        ->putJson(route('lessons.progress.update', progressLesson()), $payload)
        ->assertJsonValidationErrors($field);
})->with([
    'empty' => [[], 'position_seconds'],
    'negative position' => [['position_seconds' => -1], 'position_seconds'],
    'invalid flag' => [['completed' => 'talvez'], 'completed'],
]);

test('students can not save progress on lessons they can not watch', function () {
    $draft = Lesson::factory()->for(Module::factory()->for(Course::factory()->published()))->withVideo()->create();

    $this->actingAs(User::factory()->create())
        ->putJson(route('lessons.progress.update', $draft), ['position_seconds' => 10])
        ->assertNotFound();

    expect(LessonProgress::query()->count())->toBe(0);
});

test('guests can not save progress', function () {
    $this->putJson(route('lessons.progress.update', progressLesson()), ['position_seconds' => 10])
        ->assertUnauthorized();
});
