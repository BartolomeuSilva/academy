<?php

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonRating;
use App\Models\Module;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake();
});

function ratedLesson(): Lesson
{
    return visibleLesson(Module::factory()->for(Course::factory()->published())->create());
}

test('a student rates a lesson and can change the rating later', function () {
    $student = User::factory()->create();
    $lesson = ratedLesson();

    $this->actingAs($student)
        ->from(route('lessons.show', [$lesson->course->slug, $lesson->slug]))
        ->put(route('lessons.rating.update', $lesson), ['stars' => 4, 'feedback' => '  Faltou um exemplo.  '])
        ->assertRedirect(route('lessons.show', [$lesson->course->slug, $lesson->slug]))
        ->assertInertiaFlash('toast.message', 'Obrigado pela avaliação!');

    $this->actingAs($student)->put(route('lessons.rating.update', $lesson), ['stars' => 5, 'feedback' => '']);

    expect(LessonRating::query()->whereBelongsTo($lesson)->sole())
        ->user_id->toBe($student->id)
        ->stars->toBe(5)
        ->feedback->toBeNull();
});

test('a rating needs one to five stars and short feedback', function (array $data, string $field) {
    $this->actingAs(User::factory()->create())
        ->put(route('lessons.rating.update', ratedLesson()), $data)
        ->assertSessionHasErrors($field);

    expect(LessonRating::query()->count())->toBe(0);
})->with([
    'no stars' => [['feedback' => 'Boa'], 'stars'],
    'zero stars' => [['stars' => 0], 'stars'],
    'six stars' => [['stars' => 6], 'stars'],
    'long feedback' => [['stars' => 3, 'feedback' => str_repeat('a', LessonRating::MAX_FEEDBACK_LENGTH + 1)], 'feedback'],
]);

test('students cannot rate lessons they cannot open', function () {
    $draft = Lesson::factory()->for(Module::factory()->for(Course::factory()->published()))->withVideo()->create();

    $this->actingAs(User::factory()->create())
        ->put(route('lessons.rating.update', $draft), ['stars' => 5])
        ->assertNotFound();

    expect(LessonRating::query()->count())->toBe(0);
});

test('the lesson page shows the average rating and the student own rating', function () {
    $student = User::factory()->create();
    $lesson = ratedLesson();
    LessonRating::factory()->for($lesson)->for($student)->create(['stars' => 4, 'feedback' => 'Gostei']);
    LessonRating::factory()->for($lesson)->create(['stars' => 5]);
    LessonRating::factory()->for($lesson)->create(['stars' => 4]);
    LessonRating::factory()->for(ratedLesson())->create(['stars' => 1]);

    $this->actingAs($student)
        ->get(route('lessons.show', [$lesson->course->slug, $lesson->slug]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('rating', ['average' => 4.3, 'count' => 3, 'mine' => ['stars' => 4, 'feedback' => 'Gostei']]));

    $this->actingAs(User::factory()->create())
        ->get(route('lessons.show', [$lesson->course->slug, $lesson->slug]))
        ->assertInertia(fn (Assert $page) => $page->where('rating.mine', null));
});

test('a lesson without ratings has no average', function () {
    $lesson = ratedLesson();

    $this->actingAs(User::factory()->create())
        ->get(route('lessons.show', [$lesson->course->slug, $lesson->slug]))
        ->assertInertia(fn (Assert $page) => $page->where('rating', ['average' => null, 'count' => 0, 'mine' => null]));
});
