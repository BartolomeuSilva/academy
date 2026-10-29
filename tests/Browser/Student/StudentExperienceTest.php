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

    $first = $course->lessons()->orderBy('position')->first();

    visit('/cursos')
        ->assertSee('Onboarding')
        ->assertSee('Primeiros passos na empresa.')
        ->assertDontSee('Continue assistindo')
        ->click('@catalog-course')
        ->assertPathIs("/cursos/{$course->slug}/aulas/{$first->slug}")
        ->assertSee('Conheça a empresa')
        ->assertSee('0 de 2 aulas concluídas')
        ->click('@tab-materials')
        ->assertSee('Manual.pdf')
        ->click('@toggle-completed-button')
        ->assertSee('Aula concluída')
        ->click('@next-lesson-link')
        ->assertSee('Nossos valores')
        ->assertSee('1 de 2 aulas concluídas')
        ->assertNoJavaScriptErrors();

    expect($student->lessonProgress()->whereNotNull('completed_at')->count())->toBe(1);

    $second = $course->lessons()->where('title', 'Nossos valores')->sole();

    visit('/cursos')
        ->assertSee('Continue assistindo')
        ->assertSee('50% concluído')
        ->click('[data-test="continue-row"] [data-test="catalog-course"]')
        ->assertPathIs("/cursos/{$course->slug}/aulas/{$second->slug}");
});

test('the catalog shows the featured course and one row per category', function () {
    ['course' => $course] = publishedCourse();
    $course->update(['category' => 'Integração']);
    $course->forceFill(['banner_path' => 'banners/onboarding.jpg', 'featured_at' => now()])->save();
    $other = Course::factory()->published()->create(['title' => 'Vendas consultivas', 'category' => 'Vendas']);
    visibleLesson(Module::factory()->for($other)->create());
    $uncategorized = Course::factory()->published()->create(['title' => 'Excel básico']);
    visibleLesson(Module::factory()->for($uncategorized)->create());
    $this->actingAs(User::factory()->create());

    visit('/cursos')
        ->assertSeeIn('@featured-course', 'Onboarding')
        ->assertSeeIn('@featured-course', 'Começar curso')
        ->assertSee('Integração')
        ->assertSee('Vendas')
        ->assertSee('Outros cursos')
        ->assertSee('Excel básico')
        ->assertNoAccessibilityIssues()
        ->click('@featured-course-link')
        ->assertPathBeginsWith("/cursos/{$course->slug}/aulas/")
        ->assertNoJavaScriptErrors();
});

test('student pages have no accessibility issues in light and dark mode', function () {
    ['course' => $course] = publishedCourse();
    $lesson = $course->lessons()->orderBy('position')->first();
    $this->actingAs(User::factory()->create());

    $urls = ['/cursos', "/cursos/{$course->slug}/aulas/{$lesson->slug}"];

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
        ->click('@tab-lessons')
        ->assertSee('Nossos valores')
        ->assertNoSmoke();
});
