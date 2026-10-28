<?php

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;

test('reorders lessons and moves them between modules of the course', function () {
    $course = Course::factory()->create();
    $firstModule = Module::factory()->for($course)->create();
    $secondModule = Module::factory()->for($course)->create();
    [$a, $b] = Lesson::factory()->count(2)->for($firstModule)->create();
    $c = Lesson::factory()->for($secondModule)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.courses.lessons.order', $course), [
            'modules' => [
                ['id' => $firstModule->id, 'lesson_ids' => [$b->id]],
                ['id' => $secondModule->id, 'lesson_ids' => [$a->id, $c->id]],
            ],
        ])
        ->assertRedirect();

    expect($b->fresh())->module_id->toBe($firstModule->id)->position->toBe(0);
    expect($a->fresh())->module_id->toBe($secondModule->id)->position->toBe(0);
    expect($c->fresh())->module_id->toBe($secondModule->id)->position->toBe(1);
});

test('rejects lessons or modules from another course', function (string $case) {
    $course = Course::factory()->create();
    $module = Module::factory()->for($course)->create();
    $lesson = Lesson::factory()->for($module)->create();
    $foreignLesson = Lesson::factory()->create();

    $payload = match ($case) {
        'foreign lesson' => [['id' => $module->id, 'lesson_ids' => [$lesson->id, $foreignLesson->id]]],
        'foreign module' => [
            ['id' => $module->id, 'lesson_ids' => [$lesson->id]],
            ['id' => $foreignLesson->module_id, 'lesson_ids' => []],
        ],
        'missing lesson' => [['id' => $module->id, 'lesson_ids' => []]],
    };

    $response = $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.courses.lessons.order', $course), ['modules' => $payload]);

    $response->assertSessionHasErrors('modules');
    expect($foreignLesson->fresh()->module_id)->not->toBe($module->id);
})->with(['foreign lesson', 'foreign module', 'missing lesson']);
