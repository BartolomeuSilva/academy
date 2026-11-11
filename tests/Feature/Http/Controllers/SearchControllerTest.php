<?php

use App\Models\CommunityMessage;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonAttachment;
use App\Models\Module;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake();
});

test('the search finds courses, lessons, materials and community messages the student can open', function () {
    $course = Course::factory()->published()->create(['title' => 'Vendas pelo WhatsApp']);
    $lesson = visibleLesson(Module::factory()->for($course)->create(), ['title' => 'Respostas rápidas no WhatsApp']);
    LessonAttachment::factory()->for($lesson)->create(['original_name' => 'Modelos de WhatsApp.pdf']);
    $message = CommunityMessage::factory()->create(['body' => 'Meu WhatsApp desconectou']);

    $hiddenCourse = Course::factory()->create(['title' => 'WhatsApp avançado']);
    visibleLesson(Module::factory()->for($hiddenCourse)->create(), ['title' => 'WhatsApp oculto']);
    Lesson::factory()->for(Module::factory()->for($course))->withVideo()->create(['title' => 'WhatsApp rascunho']);

    $this->actingAs(User::factory()->create())
        ->getJson(route('search', ['q' => 'whatsapp']))
        ->assertOk()
        ->assertJsonCount(1, 'courses')
        ->assertJsonPath('courses.0.title', 'Vendas pelo WhatsApp')
        ->assertJsonPath('courses.0.url', route('courses.show', $course->slug))
        ->assertJsonCount(1, 'lessons')
        ->assertJsonPath('lessons.0.url', route('lessons.show', [$course->slug, $lesson->slug]))
        ->assertJsonPath('materials.0.title', 'Modelos de WhatsApp.pdf')
        ->assertJsonPath('community.0.title', 'Meu WhatsApp desconectou')
        ->assertJsonPath('community.0.url', route('community.channels.show', [$message->space->slug, 'mensagem' => $message->id]));
});

test('searches shorter than two characters return nothing', function () {
    Course::factory()->published()->create(['title' => 'A']);

    $this->actingAs(User::factory()->create())
        ->getJson(route('search', ['q' => ' a ']))
        ->assertExactJson(['courses' => [], 'lessons' => [], 'materials' => [], 'community' => []]);
});

test('guests cannot search', function () {
    $this->getJson(route('search', ['q' => 'vendas']))->assertUnauthorized();
});
