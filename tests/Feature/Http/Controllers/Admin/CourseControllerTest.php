<?php

use App\Enums\PublicationStatus;
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

describe('index', function () {
    test('lists courses in catalog order with their counts', function () {
        $second = Course::factory()->create(['title' => 'Segundo', 'position' => 2]);
        $first = Course::factory()->create(['title' => 'Primeiro', 'position' => 1]);
        Lesson::factory()->count(2)->for(Module::factory()->for($first))->create();

        $response = $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.courses.index'));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('admin/courses/Index')
            ->has('courses', 2)
            ->where('courses.0.id', $first->id)
            ->where('courses.0.modules_count', 1)
            ->where('courses.0.lessons_count', 2)
            ->where('courses.1.id', $second->id),
        );
    });
});

describe('store', function () {
    test('creates a draft course at the end of the catalog with a unique slug', function () {
        Course::factory()->create(['slug' => 'onboarding', 'position' => 4]);

        $response = $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.courses.store'), [
                'title' => 'Onboarding',
                'summary' => 'Primeiros passos na empresa.',
            ]);

        $course = Course::query()->where('title', 'Onboarding')->sole();
        $response->assertRedirect(route('admin.courses.edit', $course));
        expect($course)
            ->slug->toBe('onboarding-2')
            ->status->toBe(PublicationStatus::Draft)
            ->position->toBe(5);
    });

    test('requires a title and a summary', function () {
        $response = $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.courses.store'), []);

        $response->assertSessionHasErrors([
            'title' => 'O campo título é obrigatório.',
            'summary' => 'O campo resumo é obrigatório.',
        ]);
        $this->assertDatabaseCount('courses', 0);
    });
});

describe('edit', function () {
    test('shows modules and lessons in order', function () {
        $course = Course::factory()->create();
        $lateModule = Module::factory()->for($course)->create(['position' => 2]);
        $earlyModule = Module::factory()->for($course)->create(['position' => 1]);
        $secondLesson = Lesson::factory()->for($earlyModule)->create(['position' => 2]);
        $firstLesson = Lesson::factory()->for($earlyModule)->create(['position' => 1]);
        LessonAttachment::factory()->for($firstLesson)->create();

        $response = $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.courses.edit', $course));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('admin/courses/Edit')
            ->where('course.id', $course->id)
            ->where('modules.0.id', $earlyModule->id)
            ->where('modules.0.lessons.0.id', $firstLesson->id)
            ->where('modules.0.lessons.0.attachments_count', 1)
            ->where('modules.0.lessons.1.id', $secondLesson->id)
            ->where('modules.1.id', $lateModule->id),
        );
    });

    test('suggests the categories already in use', function () {
        Course::factory()->create(['category' => 'Vendas']);
        Course::factory()->create(['category' => 'Atendimento']);
        Course::factory()->create(['category' => 'Vendas']);
        $course = Course::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.courses.edit', $course))
            ->assertInertia(fn (Assert $page) => $page
                ->where('categories', ['Atendimento', 'Vendas'])
                ->where('course.featured', false));
    });
});

describe('update', function () {
    test('updates the details and records the first publication date', function () {
        $this->freezeSecond();
        $course = Course::factory()->create();

        $response = $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.courses.update', $course), [
                'title' => 'Segurança da informação',
                'slug' => 'seguranca',
                'summary' => 'Boas práticas.',
                'description' => 'Conteúdo completo.',
                'category' => 'Compliance',
                'status' => 'published',
            ]);

        $response->assertRedirect(route('admin.courses.edit', $course));
        expect($course->fresh())
            ->title->toBe('Segurança da informação')
            ->category->toBe('Compliance')
            ->slug->toBe('seguranca')
            ->status->toBe(PublicationStatus::Published)
            ->published_at->toEqual(now());
    });

    test('keeps the original publication date when published again', function () {
        $course = Course::factory()->published()->create(['published_at' => '2026-01-10 08:00:00']);

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.courses.update', $course), [
                'title' => $course->title,
                'slug' => $course->slug,
                'summary' => $course->summary,
                'status' => 'published',
            ]);

        expect($course->fresh()->published_at->toDateTimeString())->toBe('2026-01-10 08:00:00');
    });

    test('rejects a slug used by another course', function () {
        Course::factory()->create(['slug' => 'ocupado']);
        $course = Course::factory()->create();

        $response = $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.courses.update', $course), [
                'title' => $course->title,
                'slug' => 'ocupado',
                'summary' => $course->summary,
                'status' => 'draft',
            ]);

        $response->assertSessionHasErrors(['slug' => 'O valor informado em slug já está em uso.']);
    });

    test('rejects slugs that are not url friendly', function (string $slug) {
        $course = Course::factory()->create();

        $response = $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.courses.update', $course), [
                'title' => $course->title,
                'slug' => $slug,
                'summary' => $course->summary,
                'status' => 'draft',
            ]);

        $response->assertSessionHasErrors('slug');
    })->with([
        'spaces' => ['com espaço'],
        'uppercase' => ['Maiusculo'],
        'accents' => ['seguranca-da-informação'],
    ]);

    test('rejects an unknown status', function () {
        $course = Course::factory()->create();

        $response = $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.courses.update', $course), [
                'title' => $course->title,
                'slug' => $course->slug,
                'summary' => $course->summary,
                'status' => 'archived',
            ]);

        $response->assertSessionHasErrors(['status' => 'O valor selecionado para status é inválido.']);
    });
});

describe('destroy', function () {
    test('deletes the course with its content and files', function () {
        $course = Course::factory()->withCover()->featured()->create();
        $lesson = Lesson::factory()->for(Module::factory()->for($course))->create();
        $attachment = LessonAttachment::factory()->for($lesson)->create();
        Storage::put($course->cover_path, 'capa');
        Storage::put($course->banner_path, 'banner');
        Storage::put($attachment->path, 'pdf');

        $response = $this->actingAs(User::factory()->admin()->create())
            ->delete(route('admin.courses.destroy', $course));

        $response->assertRedirect(route('admin.courses.index'));
        $this->assertModelMissing($course);
        $this->assertModelMissing($lesson);
        $this->assertModelMissing($attachment);
        Storage::assertMissing([$course->cover_path, $course->banner_path, $attachment->path]);
    });
});
