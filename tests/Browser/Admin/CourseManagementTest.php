<?php

use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

// Uploads are covered by the feature tests: Pest's test server does not parse binary or nested multipart fields.
test('an admin builds a course with a module and a lesson', function () {
    $this->actingAs(User::factory()->admin()->create());

    $page = visit('/admin/cursos')
        ->assertSee('Nenhum curso ainda')
        ->click('@new-course-link')
        ->fill('title', 'Onboarding')
        ->fill('summary', 'Primeiros passos na empresa.')
        ->click('@create-course-button')
        ->assertSee('Curso criado. Agora adicione módulos e aulas.')
        ->fill('@new-module-input', 'Boas-vindas')
        ->press('Módulo')
        ->assertSee('Boas-vindas')
        ->fill('@new-lesson-input', 'Conheça a empresa')
        ->press('Aula')
        ->click('Conheça a empresa')
        ->assertSee('Vídeo da aula')
        ->assertNoJavaScriptErrors();

    $lesson = Lesson::query()->where('title', 'Conheça a empresa')->sole();
    expect($lesson->course->title)->toBe('Onboarding');
});

test('admin pages have no accessibility issues in light and dark mode', function () {
    Storage::fake();
    $this->actingAs(User::factory()->admin()->create());
    $lesson = Lesson::factory()->create();

    $pages = visit([
        '/admin/cursos',
        '/admin/cursos/criar',
        "/admin/cursos/{$lesson->course_id}/editar",
        "/admin/aulas/{$lesson->id}/editar",
    ]);

    $pages->assertNoSmoke()->assertNoAccessibilityIssues();

    visit("/admin/cursos/{$lesson->course_id}/editar")->inDarkMode()->assertNoSmoke()->assertNoAccessibilityIssues();
});

test('students do not see the administration menu', function () {
    $this->actingAs(User::factory()->create());

    visit('/cursos')->assertDontSee('Administração');

    expect(Course::query()->count())->toBe(0);
});
