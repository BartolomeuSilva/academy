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
            ->where('courses.0.duration_seconds', 720));
});

test('the course page lists the modules and lessons a student may watch', function () {
    $course = Course::factory()->published()->create();
    $module = Module::factory()->for($course)->create(['title' => 'Introdução', 'position' => 1]);
    visibleLesson($module, ['title' => 'Boas-vindas', 'position' => 1]);
    Lesson::factory()->for($module)->withVideo()->create(['title' => 'Rascunho']);
    Lesson::factory()->for($module)->published()->create(['title' => 'Sem vídeo']);
    Lesson::factory()->for(Module::factory()->for($course)->create(['title' => 'Vazio', 'position' => 2]))->create();

    $this->actingAs(User::factory()->create())
        ->get(route('courses.show', $course->slug))
        ->assertInertia(fn (Assert $page) => $page
            ->component('courses/Show')
            ->has('modules', 1)
            ->where('modules.0.title', 'Introdução')
            ->has('modules.0.lessons', 1)
            ->where('modules.0.lessons.0.title', 'Boas-vindas')
            ->where('modules.0.lessons.0.duration_seconds', 600)
            ->where('progress.total', 1));
});

test('students get not found for courses outside the catalog', function () {
    $draft = Course::factory()->create();
    visibleLesson(Module::factory()->for($draft)->create());
    $empty = Course::factory()->published()->create();
    $student = User::factory()->create();

    $this->actingAs($student)->get(route('courses.show', $draft->slug))->assertNotFound();
    $this->actingAs($student)->get(route('courses.show', $empty->slug))->assertNotFound();
});

test('admins preview draft courses with every lesson', function () {
    $course = Course::factory()->create();
    Lesson::factory()->for(Module::factory()->for($course))->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('courses.show', $course->slug))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('course.status', 'draft')
            ->has('modules.0.lessons', 1));
});

test('the resume button points to the right lesson', function (array $progress, ?int $expectedIndex, bool $started) {
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
        ->assertInertia(fn (Assert $page) => $page
            ->where('resume.lesson.id', $lessons[$expectedIndex]->id)
            ->where('resume.started', $started));
})->with([
    'not started' => [[], 0, false],
    'last watched lesson unfinished' => [[0 => [true, 10], 1 => [false, 1]], 1, true],
    'last watched lesson finished' => [[0 => [false, 10], 1 => [true, 1]], 2, true],
    'finished the last lesson, an earlier one is open' => [[0 => [false, 10], 1 => [true, 5], 2 => [true, 1]], 0, true],
    'everything finished' => [[0 => [true, 3], 1 => [true, 2], 2 => [true, 1]], 0, true],
]);

test('course descriptions are rendered without raw html', function () {
    $course = Course::factory()->published()->create(['description' => "**Bem-vindo**\n\n<script>alert(1)</script>\n\n[x](javascript:alert(1))"]);
    visibleLesson(Module::factory()->for($course)->create());

    $this->actingAs(User::factory()->create())
        ->get(route('courses.show', $course->slug))
        ->assertInertia(fn (Assert $page) => $page
            ->where('course.description_html', fn (string $html) => str_contains($html, '<strong>Bem-vindo</strong>')
                && ! str_contains($html, '<script>')
                && ! str_contains($html, 'javascript:')));
});

test('signed in users go from the home page to the catalog', function () {
    $this->actingAs(User::factory()->create())->get(route('home'))->assertRedirect(route('courses.index'));
});

test('guests and unverified users can not open the catalog', function () {
    $this->get(route('courses.index'))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->unverified()->create())
        ->get(route('courses.index'))
        ->assertRedirect(route('verification.notice'));
});
