<?php

use App\Models\Course;
use App\Models\LessonAttachment;
use App\Models\Module;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake();
});

/**
 * A published course with two lessons in one module; the first one has a PDF.
 *
 * @return array{course: Course, module: Module}
 */
function publishedCourse(): array
{
    $course = Course::factory()->published()->create(['title' => 'Onboarding', 'summary' => 'Primeiros passos na empresa.']);
    $module = Module::factory()->for($course)->create(['title' => 'Boas-vindas']);
    $first = visibleLesson($module, ['title' => 'Conheça a empresa', 'position' => 1]);
    visibleLesson($module, ['title' => 'Nossos valores', 'position' => 2]);
    LessonAttachment::factory()->for($first)->create(['original_name' => 'Manual.pdf']);

    return ['course' => $course, 'module' => $module];
}

test('a student goes from the catalog to a lesson and completes it', function () {
    ['course' => $course] = publishedCourse();
    $student = User::factory()->create();
    $this->actingAs($student);

    visit('/cursos')
        ->assertSee('Onboarding')
        ->assertSee('2 aulas')
        ->click('@catalog-course')
        ->assertPathIs("/cursos/{$course->slug}")
        ->assertSee('Começar curso')
        ->click('@resume-course-link')
        ->assertSee('Conheça a empresa')
        ->assertSee('Manual.pdf')
        ->click('@toggle-completed-button')
        ->assertSee('Aula concluída')
        ->click('@next-lesson-link')
        ->assertSee('Nossos valores')
        ->assertNoJavaScriptErrors();

    expect($student->lessonProgress()->whereNotNull('completed_at')->count())->toBe(1);
});

test('student pages have no accessibility issues in light and dark mode', function () {
    ['course' => $course] = publishedCourse();
    $lesson = $course->lessons()->orderBy('position')->first();
    $this->actingAs(User::factory()->create());

    $urls = ['/cursos', "/cursos/{$course->slug}", "/cursos/{$course->slug}/aulas/{$lesson->slug}"];

    visit($urls)->assertNoSmoke()->assertNoAccessibilityIssues();

    foreach ($urls as $url) {
        visit($url)->inDarkMode()->assertNoAccessibilityIssues();
    }
});

test('the lesson page works on a phone', function () {
    ['course' => $course] = publishedCourse();
    $lesson = $course->lessons()->orderBy('position')->first();
    $this->actingAs(User::factory()->create());

    visit("/cursos/{$course->slug}/aulas/{$lesson->slug}")
        ->on()->mobile()
        ->assertSee('Conheça a empresa')
        ->assertSee('Marcar como concluída')
        ->assertNoSmoke();
});
