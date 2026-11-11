<?php

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonAttachment;
use App\Models\Module;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake();
});

test('guests are sent to the login page', function () {
    $this->get(route('materials.index'))->assertRedirect(route('login'));
});

test('the library lists only materials of lessons students can open, in course order', function () {
    $second = Course::factory()->published()->create(['title' => 'Excel', 'position' => 2]);
    LessonAttachment::factory()->for(visibleLesson(Module::factory()->for($second)->create()))->create(['original_name' => 'Atalhos.pdf']);
    $first = Course::factory()->published()->create(['title' => 'Vendas', 'position' => 1]);
    $lesson = visibleLesson(Module::factory()->for($first)->create(), ['title' => 'Objeções']);
    LessonAttachment::factory()->for($lesson)->create(['original_name' => 'Roteiro.pdf']);

    $draftLesson = Lesson::factory()->for(Module::factory()->for($first))->withVideo()->create();
    LessonAttachment::factory()->for($draftLesson)->create(['original_name' => 'Rascunho.pdf']);
    $draftCourse = Course::factory()->create();
    LessonAttachment::factory()->for(visibleLesson(Module::factory()->for($draftCourse)->create()))->create(['original_name' => 'Curso oculto.pdf']);

    $this->actingAs(User::factory()->create())
        ->get(route('materials.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('materials/Index')
            ->has('materials.data', 2)
            ->where('materials.data.0.original_name', 'Roteiro.pdf')
            ->where('materials.data.0.course', ['slug' => $first->slug, 'title' => 'Vendas'])
            ->where('materials.data.0.lesson', ['slug' => $lesson->slug, 'title' => 'Objeções'])
            ->where('materials.data.1.original_name', 'Atalhos.pdf')
            ->where('courses', fn ($courses) => collect($courses)->pluck('title')->all() === ['Vendas', 'Excel']));
});

test('students search materials by name and filter them by course', function () {
    $sales = Course::factory()->published()->create();
    $salesLesson = visibleLesson(Module::factory()->for($sales)->create());
    LessonAttachment::factory()->for($salesLesson)->create(['original_name' => 'Roteiro de perguntas.pdf']);
    LessonAttachment::factory()->for($salesLesson)->create(['original_name' => 'Tabela de preços.pdf']);
    $excel = Course::factory()->published()->create();
    LessonAttachment::factory()->for(visibleLesson(Module::factory()->for($excel)->create()))->create(['original_name' => 'Roteiro do Excel.pdf']);
    $student = User::factory()->create();

    $this->actingAs($student)
        ->get(route('materials.index', ['busca' => 'roteiro']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('materials.data', 2)
            ->where('filters.search', 'roteiro'));

    $this->actingAs($student)
        ->get(route('materials.index', ['busca' => 'roteiro', 'curso' => $sales->slug]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('materials.data', 1)
            ->where('materials.data.0.original_name', 'Roteiro de perguntas.pdf')
            ->where('filters.course', $sales->slug));
});

test('the library is paginated', function () {
    $lesson = visibleLesson(Module::factory()->for(Course::factory()->published())->create());
    LessonAttachment::factory()->count(25)->for($lesson)->create();

    $this->actingAs(User::factory()->create())
        ->get(route('materials.index', ['page' => 2]))
        ->assertInertia(fn (Assert $page) => $page->has('materials.data', 1)->where('materials.last_page', 2));
});
