<?php

use App\Models\Comment;
use App\Models\Course;
use App\Models\Module;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake();
});

test('a student comments, replies, edits and deletes on a lesson', function () {
    $lesson = visibleLesson(Module::factory()->for(Course::factory()->published())->create());
    $question = Comment::factory()->for($lesson)->create(['body' => 'Onde fica o material?']);
    $student = User::factory()->create();
    $this->actingAs($student);

    $page = visit("/cursos/{$lesson->course->slug}/aulas/{$lesson->slug}")
        ->click('@tab-comments')
        ->assertSee('Onde fica o material?')
        ->click('@reply-comment-button')
        ->fill('@reply-input', 'Está na seção Materiais.')
        ->click('@send-reply-button')
        ->assertSee('Está na seção Materiais.')
        ->fill('@new-comment-input', 'Gostei muito da aula!')
        ->click('@send-comment-button')
        ->assertSee('Gostei muito da aula!')
        ->assertSee('Comentários (3)')
        ->assertNoAccessibilityIssues()
        ->assertNoJavaScriptErrors();

    expect($question->replies()->sole()->user_id)->toBe($student->id);

    $page->click('@edit-comment-button')
        ->fill('@edit-comment-input', 'Gostei demais da aula!')
        ->press('Salvar')
        ->assertSee('Gostei demais da aula!')
        ->assertSee('(editado)')
        ->click('@delete-comment-button')
        ->click('@confirm-dialog-button')
        ->assertDontSee('Gostei demais da aula!')
        ->assertNoJavaScriptErrors();
});

test('the moderation page has no accessibility issues', function () {
    Comment::factory()->count(3)->create();
    $this->actingAs(User::factory()->admin()->create());

    visit('/admin/comentarios')->assertSee('Comentários')->assertNoSmoke()->assertNoAccessibilityIssues();
    visit('/admin/comentarios')->inDarkMode()->assertNoAccessibilityIssues();
});
