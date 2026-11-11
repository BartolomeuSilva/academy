<?php

use App\Models\CommunityReply;
use App\Models\CommunitySpace;
use App\Models\CommunityTopic;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('the community starts with the general, introductions and announcements spaces', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('community.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('community/Index')
            ->where('spaces', fn ($spaces) => collect($spaces)->pluck('name')->all() === ['Avisos', 'Geral', 'Apresente-se'])
            ->where('spaces.0.can_post', false)
            ->where('spaces.1.can_post', true));
});

test('topics are listed with pinned ones first, then by latest activity', function () {
    $space = CommunitySpace::factory()->create();
    CommunityTopic::factory()->for($space, 'space')->create(['title' => 'Antigo', 'last_activity_at' => now()->subDays(2)]);
    CommunityTopic::factory()->for($space, 'space')->create(['title' => 'Recente', 'last_activity_at' => now()->subHour()]);
    CommunityTopic::factory()->for($space, 'space')->pinned()->create(['title' => 'Regras', 'last_activity_at' => now()->subWeek()]);

    $this->actingAs(User::factory()->create())
        ->get(route('community.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('topics.data', fn ($topics) => collect($topics)->pluck('title')->all() === ['Regras', 'Recente', 'Antigo'])
            ->where('topics.data.0.pinned', true)
            ->where('topics.data.0.space.slug', $space->slug));
});

test('topics are filtered by space, unanswered, own topics and search', function () {
    $student = User::factory()->create();
    $zendflow = CommunitySpace::factory()->create(['slug' => 'zendflow']);
    $mine = CommunityTopic::factory()->for($zendflow, 'space')->for($student)->create(['title' => 'Como integro o WhatsApp?']);
    $answered = CommunityTopic::factory()->for($zendflow, 'space')->create(['title' => 'Erro ao importar contatos']);
    CommunityReply::factory()->for($answered, 'topic')->create();
    CommunityTopic::factory()->create(['title' => 'Outro produto']);

    $titles = fn (array $query) => $this->actingAs($student)->get(route('community.index', $query))
        ->viewData('page')['props']['topics']['data'];

    expect(collect($titles(['espaco' => 'zendflow']))->pluck('title')->sort()->values()->all())
        ->toBe(['Como integro o WhatsApp?', 'Erro ao importar contatos'])
        ->and(collect($titles(['espaco' => 'zendflow', 'filtro' => 'sem-resposta']))->pluck('title')->all())
        ->toBe(['Como integro o WhatsApp?'])
        ->and(collect($titles(['filtro' => 'meus']))->pluck('id')->all())->toBe([$mine->id])
        ->and(collect($titles(['busca' => 'importar']))->pluck('title')->all())->toBe(['Erro ao importar contatos']);
});

test('a student starts a topic', function () {
    $student = User::factory()->create();
    $space = CommunitySpace::query()->where('slug', 'geral')->sole();

    $response = $this->actingAs($student)->post(route('community.topics.store'), [
        'space_id' => $space->id,
        'title' => '  Como  emitir a nota fiscal?  ',
        'body' => 'Não encontrei a opção.',
    ]);

    $topic = CommunityTopic::query()->sole();
    $response->assertRedirect(route('community.topics.show', $topic));
    expect($topic)
        ->title->toBe('Como emitir a nota fiscal?')
        ->user_id->toBe($student->id)
        ->space_id->toBe($space->id);
});

test('only the team starts topics in team-only spaces', function () {
    $announcements = CommunitySpace::query()->where('slug', 'avisos')->sole();
    $data = ['space_id' => $announcements->id, 'title' => 'Nova versão', 'body' => 'Saiu a versão 2.0.'];

    $this->actingAs(User::factory()->create())->post(route('community.topics.store'), $data)->assertForbidden();
    $this->actingAs(User::factory()->admin()->create())->post(route('community.topics.store'), $data)->assertRedirect();

    expect(CommunityTopic::query()->count())->toBe(1);
});

test('a topic needs a space, a title and a message', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('community.topics.store'), ['space_id' => 999, 'title' => 'Oi', 'body' => ''])
        ->assertSessionHasErrors(['space_id', 'title', 'body']);
});

test('the create form offers only the spaces the user may post in', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('community.topics.create', ['espaco' => 'geral']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('community/Create')
            ->where('spaces', fn ($spaces) => collect($spaces)->pluck('slug')->all() === ['geral', 'apresente-se'])
            ->where('space', 'geral'));
});

test('a topic shows its answer and replies with what the viewer may do', function () {
    $author = User::factory()->create();
    $topic = CommunityTopic::factory()->for($author)->create(['body' => '**Ajuda** <script>x</script>']);
    $first = CommunityReply::factory()->for($topic, 'topic')->for(User::factory()->admin()->create(['name' => 'Equipe']))->create();
    CommunityReply::factory()->for($topic, 'topic')->create();
    $topic->forceFill(['solution_reply_id' => $first->id])->save();

    $this->actingAs($author)
        ->get(route('community.topics.show', $topic))
        ->assertInertia(fn (Assert $page) => $page
            ->component('community/Show')
            ->where('topic.body_html', fn (string $html) => str_contains($html, '<strong>Ajuda</strong>') && ! str_contains($html, '<script>'))
            ->where('topic.can.update', true)
            ->where('topic.can.solve', true)
            ->where('topic.can.moderate', false)
            ->where('topic.can.report', false)
            ->where('solution.id', $first->id)
            ->where('solution.author', ['name' => 'Equipe', 'is_team' => true])
            ->has('replies.data', 2)
            ->where('replies.data.0.is_solution', true)
            ->where('replies.data.1.can.report', true)
            ->where('spaces', []));
});

test('only the author edits a topic', function () {
    $topic = CommunityTopic::factory()->create(['title' => 'Antes']);
    $data = ['title' => 'Depois', 'body' => 'Texto novo'];

    $this->actingAs(User::factory()->admin()->create())->put(route('community.topics.update', $topic), $data)->assertForbidden();
    $this->actingAs($topic->user)->put(route('community.topics.update', $topic), $data)->assertRedirect();

    expect($topic->refresh())->title->toBe('Depois')->edited_at->not->toBeNull();
});

test('authors and admins delete topics, other students cannot', function () {
    $topic = CommunityTopic::factory()->create();

    $this->actingAs(User::factory()->create())->delete(route('community.topics.destroy', $topic))->assertForbidden();
    $this->actingAs($topic->user)->delete(route('community.topics.destroy', $topic))
        ->assertRedirect(route('community.index'))
        ->assertInertiaFlash('toast.message', 'Tópico excluído.');

    expect($topic->refresh()->trashed())->toBeTrue();

    $other = CommunityTopic::factory()->create();
    $this->actingAs(User::factory()->admin()->create())->delete(route('community.topics.destroy', $other));
    expect($other->refresh()->trashed())->toBeTrue();
});

test('only the team moves a topic to another space', function () {
    $topic = CommunityTopic::factory()->create();
    $general = CommunitySpace::query()->where('slug', 'geral')->sole();

    $this->actingAs($topic->user)->put(route('community.topics.move', $topic), ['space_id' => $general->id])->assertForbidden();
    $this->actingAs(User::factory()->admin()->create())->put(route('community.topics.move', $topic), ['space_id' => $general->id])->assertRedirect();

    expect($topic->refresh()->space_id)->toBe($general->id);
});

test('only the team pins topics', function () {
    $topic = CommunityTopic::factory()->create();

    $this->actingAs($topic->user)->post(route('community.topics.pin.store', $topic))->assertForbidden();
    $this->actingAs(User::factory()->admin()->create())->post(route('community.topics.pin.store', $topic));
    expect($topic->refresh()->pinned_at)->not->toBeNull();

    $this->actingAs(User::factory()->admin()->create())->delete(route('community.topics.pin.destroy', $topic));
    expect($topic->refresh()->pinned_at)->toBeNull();
});

test('the author or the team marks the reply that solved the topic', function () {
    $topic = CommunityTopic::factory()->create();
    $reply = CommunityReply::factory()->for($topic, 'topic')->create();
    $replyElsewhere = CommunityReply::factory()->create();

    $this->actingAs(User::factory()->create())->post(route('community.topics.solution.store', [$topic, $reply]))->assertForbidden();
    $this->actingAs($topic->user)->post(route('community.topics.solution.store', [$topic, $replyElsewhere]))->assertNotFound();
    $this->actingAs($topic->user)->post(route('community.topics.solution.store', [$topic, $reply]))->assertRedirect();
    expect($topic->refresh()->solution_reply_id)->toBe($reply->id);

    $this->actingAs(User::factory()->admin()->create())->delete(route('community.topics.solution.destroy', $topic));
    expect($topic->refresh()->solution_reply_id)->toBeNull();
});
