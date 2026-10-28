<?php

use App\Enums\PublicationStatus;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonAttachment;
use App\Models\Module;
use App\Models\User;
use App\Models\Video;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

test('adds a draft lesson to the end of the module', function () {
    $module = Module::factory()->create();
    Lesson::factory()->for($module)->create(['position' => 2]);

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.modules.lessons.store', $module), ['title' => 'Boas-vindas'])
        ->assertRedirect();

    $lesson = Lesson::query()->where('title', 'Boas-vindas')->sole();
    expect($lesson)
        ->course_id->toBe($module->course_id)
        ->slug->toBe('boas-vindas')
        ->status->toBe(PublicationStatus::Draft)
        ->position->toBe(3);
});

test('makes lesson slugs unique only within the course', function () {
    $course = Course::factory()->create();
    Lesson::factory()->for(Module::factory()->for($course))->create(['slug' => 'boas-vindas']);
    Lesson::factory()->create(['slug' => 'introducao']);
    $module = Module::factory()->for($course)->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.modules.lessons.store', $module), ['title' => 'Boas-vindas']);
    $this->actingAs($admin)->post(route('admin.modules.lessons.store', $module), ['title' => 'Introdução']);

    expect($module->lessons()->pluck('slug')->all())->toBe(['boas-vindas-2', 'introducao']);
});

test('shows the lesson with its attachments', function () {
    Storage::fake();
    $lesson = Lesson::factory()->create();
    $attachment = LessonAttachment::factory()->for($lesson)->create(['original_name' => 'apostila.pdf']);

    $response = $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.lessons.edit', $lesson));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('admin/lessons/Edit')
        ->where('lesson.id', $lesson->id)
        ->where('lesson.course.id', $lesson->course_id)
        ->where('attachments.0.id', $attachment->id)
        ->where('attachments.0.original_name', 'apostila.pdf')
        ->where('attachments.0.kind', 'pdf')
        ->has('attachments.0.url'),
    );
});

test('updates the lesson details', function () {
    $lesson = Lesson::factory()->create();

    $response = $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.lessons.update', $lesson), [
            'title' => 'Políticas internas',
            'slug' => 'politicas',
            'description' => 'Leia antes de começar.',
        ]);

    $response->assertRedirect(route('admin.lessons.edit', $lesson));
    expect($lesson->fresh())
        ->title->toBe('Políticas internas')
        ->slug->toBe('politicas')
        ->description->toBe('Leia antes de começar.');
});

test('rejects a slug used by another lesson of the same course', function () {
    $course = Course::factory()->create();
    $module = Module::factory()->for($course)->create();
    Lesson::factory()->for($module)->create(['slug' => 'ocupado']);
    $lesson = Lesson::factory()->for($module)->create();

    $response = $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.lessons.update', $lesson), [
            'title' => $lesson->title,
            'slug' => 'ocupado',
        ]);

    $response->assertSessionHasErrors(['slug' => 'O valor informado em slug já está em uso.']);
});

test('allows a slug used by a lesson of another course', function () {
    Lesson::factory()->create(['slug' => 'introducao']);
    $lesson = Lesson::factory()->create();

    $response = $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.lessons.update', $lesson), [
            'title' => $lesson->title,
            'slug' => 'introducao',
        ]);

    $response->assertSessionHasNoErrors();
});

test('deletes the lesson with its files and returns to the course', function () {
    Storage::fake();
    $lesson = Lesson::factory()->create();
    $attachment = LessonAttachment::factory()->for($lesson)->create();
    Storage::put($attachment->path, 'pdf');

    $response = $this->actingAs(User::factory()->admin()->create())
        ->delete(route('admin.lessons.destroy', $lesson));

    $response->assertRedirect(route('admin.courses.edit', $lesson->course_id));
    $this->assertModelMissing($lesson);
    Storage::assertMissing($attachment->path);
});

test('publishes a lesson that has a ready video', function () {
    $lesson = Lesson::factory()->create();
    $video = Video::factory()->ready()->for($lesson)->create();
    $lesson->video()->associate($video)->save();

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.lessons.update', $lesson), [
            'title' => $lesson->title,
            'slug' => $lesson->slug,
            'status' => 'published',
        ])
        ->assertSessionHasNoErrors();

    expect($lesson->fresh()->status)->toBe(PublicationStatus::Published);
});

test('refuses to publish a lesson without a ready video', function () {
    $lesson = Lesson::factory()->create();
    Video::factory()->queued()->for($lesson)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.lessons.update', $lesson), [
            'title' => $lesson->title,
            'slug' => $lesson->slug,
            'status' => 'published',
        ])
        ->assertSessionHasErrors(['status' => 'A aula só pode ser publicada depois que o vídeo estiver pronto.']);

    expect($lesson->fresh()->status)->toBe(PublicationStatus::Draft);
});

test('shows the current video and the upload in progress', function () {
    Storage::fake();
    $lesson = Lesson::factory()->create();
    $current = Video::factory()->ready()->for($lesson)->create();
    $lesson->video()->associate($current)->save();
    $pending = Video::factory()->queued()->for($lesson)->create();

    $response = $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.lessons.edit', $lesson));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('video.id', $current->id)
        ->where('video.renditions', ['480p', '720p'])
        ->where('video.playlist_url', route('videos.playlist', [$current, 'master']))
        ->where('pending_video.id', $pending->id)
        ->where('pending_video.status', 'queued'),
    );
});

test('deleting a lesson also deletes its video files', function () {
    Storage::fake();
    $lesson = Lesson::factory()->create();
    $video = Video::factory()->ready()->for($lesson)->create();
    Storage::put("{$video->hlsDirectory()}/master.m3u8", '#EXTM3U');

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('admin.lessons.destroy', $lesson));

    Storage::assertMissing("{$video->hlsDirectory()}/master.m3u8");
});
