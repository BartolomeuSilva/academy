<?php

use App\Models\Comment;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake();
});

test('the community gathers the latest questions from every lesson students can open', function () {
    $course = Course::factory()->published()->create(['title' => 'Vendas']);
    $lesson = visibleLesson(Module::factory()->for($course)->create(), ['title' => 'Objeções']);
    $older = Comment::factory()->for($lesson)->create(['body' => 'Primeira dúvida']);
    $newer = Comment::factory()->for($lesson)->create(['body' => 'Segunda dúvida']);
    Comment::factory()->for($lesson)->create(['parent_id' => $older->id]);
    Comment::factory()->for(Lesson::factory()->for(Module::factory()->for($course))->withVideo())->create(['body' => 'Em rascunho']);

    $this->actingAs(User::factory()->create())
        ->get(route('community.lessons'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('community/Lessons')
            ->has('discussions.data', 2)
            ->where('discussions.data.0.id', $newer->id)
            ->where('discussions.data.0.course.title', 'Vendas')
            ->where('discussions.data.0.lesson.title', 'Objeções')
            ->where('discussions.data.1.replies_count', 1));

    $this->actingAs(User::factory()->create())
        ->get(route('community.lessons', ['filtro' => 'sem-resposta']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('discussions.data', 1)
            ->where('discussions.data.0.id', $newer->id)
            ->where('filters.filter', 'sem-resposta'));
});
