<?php

use App\Models\Course;
use App\Models\LessonAttachment;
use App\Models\LessonProgress;
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
        ->assertDontSee('Sua próxima aula')
        ->click('@catalog-course')
        ->assertPathIs("/cursos/{$course->slug}/aulas/{$first->slug}")
        ->assertSee('Conheça a empresa')
        ->assertSee('Aula 1')
        ->assertDontSee('Retomando de')
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
        ->assertSeeIn('@next-lesson', 'Nossos valores')
        ->assertSeeIn('@next-lesson', 'Falta 1 aula neste curso')
        ->assertSeeIn('@progress-course', '1 de 2 aulas')
        ->assertSee('50% concluído')
        ->click('@next-lesson-link')
        ->assertPathIs("/cursos/{$course->slug}/aulas/{$second->slug}")
        ->assertSee('Aula 2');
});

test('a lesson resumes where the student stopped and can start over', function () {
    ['course' => $course] = publishedCourse();
    $lesson = $course->lessons()->orderBy('position')->first();
    $student = User::factory()->create();
    LessonProgress::factory()->for($student)->for($lesson)->create(['last_position_seconds' => 95]);
    $this->actingAs($student);

    visit("/cursos/{$course->slug}/aulas/{$lesson->slug}")
        ->assertSeeIn('@resume-notice', 'Retomando de 1:35')
        ->click('@restart-lesson-button')
        ->assertMissing('@resume-notice')
        ->assertNoJavaScriptErrors();
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
    $student = User::factory()->create();
    LessonProgress::factory()->for($student)->for($lesson)->create(['last_position_seconds' => 95]);
    $this->actingAs($student);

    $urls = ['/cursos', "/cursos/{$course->slug}/aulas/{$lesson->slug}", '/materiais'];

    visit($urls)->assertNoSmoke()->assertNoAccessibilityIssues();

    foreach ($urls as $url) {
        visitInDarkTheme($url)->assertNoAccessibilityIssues();
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

test('a student rates a lesson and the team reads the feedback', function () {
    ['course' => $course] = publishedCourse();
    $lesson = $course->lessons()->orderBy('position')->first();
    $this->actingAs(User::factory()->create(['name' => 'Ana Souza']));

    visit("/cursos/{$course->slug}/aulas/{$lesson->slug}")
        ->assertSee('O que achou desta aula?')
        ->click('label:has([data-test="rating-star-4"])')
        ->assertSee('Muito boa')
        ->fill('@rating-feedback-input', 'Faltou um exemplo prático.')
        ->click('@send-rating-button')
        ->assertSee('Obrigado pela avaliação!')
        ->assertSee('Sua nota:')
        ->assertSeeIn('@rating-average', '4')
        ->assertNoJavaScriptErrors();

    $this->actingAs(User::factory()->admin()->create());

    visit("/admin/aulas/{$lesson->id}/editar")
        ->assertSeeIn('@lesson-ratings-summary', 'Faltou um exemplo prático.')
        ->assertSeeIn('@lesson-ratings-summary', 'Ana Souza');
});

test('a student finds a material in the library and opens its lesson', function () {
    ['course' => $course] = publishedCourse();
    $this->actingAs(User::factory()->create());

    visit('/cursos')
        ->click('Materiais')
        ->assertPathIs('/materiais')
        ->assertSeeIn('@library-material', 'Manual.pdf')
        ->assertSeeIn('@library-material', 'Onboarding')
        ->fill('@material-search', 'planilha')
        ->assertSee('Nenhum material encontrado')
        ->fill('@material-search', 'manual')
        ->assertSee('Manual.pdf')
        ->click('text=Aula: Conheça a empresa')
        ->assertPathBeginsWith("/cursos/{$course->slug}/aulas/")
        ->assertNoJavaScriptErrors();
});

test('phones navigate with the bottom bar', function () {
    publishedCourse();
    $this->actingAs(User::factory()->create());

    visit('/cursos')
        ->on()->mobile()
        ->assertVisible('@bottom-nav')
        ->click('[data-test="bottom-nav"] >> text=Materiais')
        ->assertPathIs('/materiais')
        ->assertSee('Manual.pdf')
        ->assertNoSmoke();
});
