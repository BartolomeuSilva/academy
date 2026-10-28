<?php

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonAttachment;
use App\Models\Module;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

test('adds a module to the end of the course', function () {
    $course = Course::factory()->create();
    Module::factory()->for($course)->create(['position' => 3]);

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.courses.modules.store', $course), ['title' => 'Introdução'])
        ->assertRedirect();

    $this->assertDatabaseHas('modules', ['course_id' => $course->id, 'title' => 'Introdução', 'position' => 4]);
});

test('requires a module title', function () {
    $course = Course::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.courses.modules.store', $course), ['title' => ''])
        ->assertSessionHasErrors(['title' => 'O campo título é obrigatório.']);
});

test('renames a module', function () {
    $module = Module::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.modules.update', $module), ['title' => 'Novo nome'])
        ->assertRedirect();

    expect($module->fresh()->title)->toBe('Novo nome');
});

test('deletes a module with its lessons and files', function () {
    Storage::fake();
    $module = Module::factory()->create();
    $lesson = Lesson::factory()->for($module)->create();
    $attachment = LessonAttachment::factory()->for($lesson)->create();
    Storage::put($attachment->path, 'pdf');

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('admin.modules.destroy', $module))
        ->assertRedirect();

    $this->assertModelMissing($module);
    $this->assertModelMissing($lesson);
    Storage::assertMissing($attachment->path);
});

test('saves the module order', function () {
    $course = Course::factory()->create();
    [$first, $second] = Module::factory()->count(2)->for($course)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.courses.modules.order', $course), ['ids' => [$second->id, $first->id]])
        ->assertRedirect();

    expect([$second->fresh()->position, $first->fresh()->position])->toBe([0, 1]);
});

test('rejects ordering modules from another course', function () {
    $course = Course::factory()->create();
    $own = Module::factory()->for($course)->create();
    $foreign = Module::factory()->create();

    $response = $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.courses.modules.order', $course), ['ids' => [$own->id, $foreign->id]]);

    $response->assertSessionHasErrors('ids');
    expect($foreign->fresh()->position)->toBe(0);
});
