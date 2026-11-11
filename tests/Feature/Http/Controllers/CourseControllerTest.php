<?php

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Module;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake();
});

test('the catalog lists published courses that have a visible lesson, in order', function () {
    $second = Course::factory()->published()->create(['title' => 'Segundo', 'position' => 2]);
    visibleLesson(Module::factory()->for($second)->create());
    $first = Course::factory()->published()->create(['title' => 'Primeiro', 'position' => 1]);
    visibleLesson(Module::factory()->for($first)->create());

    $draft = Course::factory()->create(['title' => 'Rascunho']);
    visibleLesson(Module::factory()->for($draft)->create());
    $withoutVisibleLessons = Course::factory()->published()->create(['title' => 'Vazio']);
    Lesson::factory()->for(Module::factory()->for($withoutVisibleLessons))->published()->create();
    Lesson::factory()->for(Module::factory()->for($withoutVisibleLessons))->withVideo()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('courses.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('courses/Index')
            ->where('courses.0.title', 'Primeiro')
            ->where('courses.1.title', 'Segundo')
            ->has('courses', 2));
});

test('catalog cards count only visible lessons and the student own completions', function () {
    $student = User::factory()->create();
    $course = Course::factory()->published()->create();
    $module = Module::factory()->for($course)->create();
    $completed = visibleLesson($module, durationSeconds: 300);
    visibleLesson($module, durationSeconds: 420);
    Lesson::factory()->for($module)->withVideo(9000)->create();
    LessonProgress::factory()->completed()->for($student)->for($completed)->create();
    LessonProgress::factory()->completed()->for(User::factory())->for(Lesson::query()->latest('id')->first())->create();

    $this->actingAs($student)
        ->get(route('courses.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('courses.0.lessons_count', 2)
            ->where('courses.0.completed_lessons_count', 1)
            ->where('courses.0.duration_seconds', 720)
            ->where('courses.0.started', true));
});

test('the catalog features the spotlighted course and tells each course category', function () {
    $featured = Course::factory()->published()->featured()->create(['category' => 'Vendas', 'banner_mobile_path' => 'banners/celular.jpg']);
    visibleLesson(Module::factory()->for($featured)->create());
    $hiddenFeatured = Course::factory()->featured()->create();
    visibleLesson(Module::factory()->for($hiddenFeatured)->create());

    $this->actingAs(User::factory()->create())
        ->get(route('courses.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('featured.id', $featured->id)
            ->where('featured.banner_url', fn (string $url) => str_contains($url, $featured->banner_path))
            ->where('featured.banner_mobile_url', fn (string $url) => str_contains($url, 'banners/celular.jpg'))
            ->where('courses.0.category', 'Vendas'));
});

test('a featured course outside the catalog is not featured', function () {
    $course = Course::factory()->featured()->create();
    visibleLesson(Module::factory()->for($course)->create());

    $this->actingAs(User::factory()->create())
        ->get(route('courses.index'))
        ->assertInertia(fn (Assert $page) => $page->where('featured', null));
});

test('a course counts as started once the student watched any visible lesson', function () {
    $student = User::factory()->create();
    $course = Course::factory()->published()->create();
    $module = Module::factory()->for($course)->create();
    visibleLesson($module);
    $draft = Lesson::factory()->for($module)->withVideo()->create();
    LessonProgress::factory()->for($student)->for($draft)->create();
    LessonProgress::factory()->for(User::factory())->for(visibleLesson($module))->create();

    $this->actingAs($student)
        ->get(route('courses.index'))
        ->assertInertia(fn (Assert $page) => $page->where('courses.0.started', false));
});

test('the catalog suggests the next lesson of the unfinished course watched last', function () {
    $student = User::factory()->create();
    $older = Course::factory()->published()->create(['position' => 1]);
    LessonProgress::factory()->for($student)->for(visibleLesson(Module::factory()->for($older)->create()))
        ->create(['updated_at' => now()->subDay()]);

    $course = Course::factory()->published()->create(['title' => 'Vendas consultivas', 'position' => 2]);
    $module = Module::factory()->for($course)->create();
    $watched = visibleLesson($module, ['position' => 1], durationSeconds: 300);
    $next = visibleLesson($module, ['position' => 2, 'title' => 'Objeções'], durationSeconds: 420);
    visibleLesson($module, ['position' => 3], durationSeconds: 600);
    LessonProgress::factory()->completed()->for($student)->for($watched)->create(['updated_at' => now()->subMinute()]);

    $finished = Course::factory()->published()->create(['position' => 3]);
    LessonProgress::factory()->completed()->for($student)->for(visibleLesson(Module::factory()->for($finished)->create()))->create();

    $this->actingAs($student)
        ->get(route('courses.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('next_lesson.course', ['slug' => $course->slug, 'title' => 'Vendas consultivas'])
            ->where('next_lesson.lesson', ['slug' => $next->slug, 'title' => 'Objeções', 'number' => 2, 'duration_seconds' => 420])
            ->where('next_lesson.started', true)
            ->where('next_lesson.remaining_lessons', 2)
            ->where('next_lesson.remaining_seconds', 1020)
            ->where('courses.1.last_watched_at', fn (?string $watchedAt) => $watchedAt !== null)
            ->where('courses.2.last_watched_at', fn (?string $watchedAt) => $watchedAt !== null));
});

test('the catalog suggests no lesson before the student starts a course or after finishing them all', function () {
    $student = User::factory()->create();
    $course = Course::factory()->published()->create();
    $lesson = visibleLesson(Module::factory()->for($course)->create());

    $this->actingAs($student)
        ->get(route('courses.index'))
        ->assertInertia(fn (Assert $page) => $page->where('next_lesson', null)->where('courses.0.last_watched_at', null));

    LessonProgress::factory()->completed()->for($student)->for($lesson)->create();

    $this->actingAs($student)
        ->get(route('courses.index'))
        ->assertInertia(fn (Assert $page) => $page->where('next_lesson', null));
});

test('students get not found for courses outside the catalog', function () {
    $draft = Course::factory()->create();
    visibleLesson(Module::factory()->for($draft)->create());
    $empty = Course::factory()->published()->create();
    $student = User::factory()->create();

    $this->actingAs($student)->get(route('courses.show', $draft->slug))->assertNotFound();
    $this->actingAs($student)->get(route('courses.show', $empty->slug))->assertNotFound();
});

test('admins preview draft courses from their first lesson', function () {
    $course = Course::factory()->create();
    $lesson = Lesson::factory()->for(Module::factory()->for($course))->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('courses.show', $course->slug))
        ->assertRedirect(route('lessons.show', [$course->slug, $lesson->slug]));
});

test('admins opening a course without lessons go back to editing it', function () {
    $course = Course::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('courses.show', $course->slug))
        ->assertRedirect(route('admin.courses.edit', $course))
        ->assertInertiaFlash('toast.message', 'Este curso ainda não tem aulas.');
});

test('opening a course goes to the lesson to continue from', function (array $progress, int $expectedIndex) {
    $student = User::factory()->create();
    $course = Course::factory()->published()->create();
    $module = Module::factory()->for($course)->create();
    $lessons = collect(range(0, 2))->map(fn (int $position) => visibleLesson($module, ['position' => $position]));

    foreach ($progress as $index => [$completed, $minutesAgo]) {
        LessonProgress::factory()->for($student)->for($lessons[$index])->create([
            'completed_at' => $completed ? now() : null,
            'updated_at' => now()->subMinutes($minutesAgo),
        ]);
    }

    $this->actingAs($student)
        ->get(route('courses.show', $course->slug))
        ->assertRedirect(route('lessons.show', [$course->slug, $lessons[$expectedIndex]->slug]));
})->with([
    'not started' => [[], 0],
    'last watched lesson unfinished' => [[0 => [true, 10], 1 => [false, 1]], 1],
    'last watched lesson finished' => [[0 => [false, 10], 1 => [true, 1]], 2],
    'finished the last lesson, an earlier one is open' => [[0 => [false, 10], 1 => [true, 5], 2 => [true, 1]], 0],
    'everything finished' => [[0 => [true, 3], 1 => [true, 2], 2 => [true, 1]], 0],
]);

test('signed in users go from the home page to the catalog', function () {
    $this->actingAs(User::factory()->create())->get(route('home'))->assertRedirect(route('courses.index'));
});

test('guests and unverified users can not open the catalog', function () {
    $this->get(route('courses.index'))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->unverified()->create())
        ->get(route('courses.index'))
        ->assertRedirect(route('verification.notice'));
});

test('my courses lists the catalog with the student progress', function () {
    $student = User::factory()->create();
    $course = Course::factory()->published()->create(['title' => 'Vendas']);
    $lesson = visibleLesson(Module::factory()->for($course)->create());
    LessonProgress::factory()->completed()->for($student)->for($lesson)->create();
    Course::factory()->create(['title' => 'Rascunho']);

    $this->actingAs($student)
        ->get(route('courses.library'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('courses/Library')
            ->has('courses', 1)
            ->where('courses.0.title', 'Vendas')
            ->where('courses.0.completed_lessons_count', 1)
            ->where('courses.0.started', true));
});
