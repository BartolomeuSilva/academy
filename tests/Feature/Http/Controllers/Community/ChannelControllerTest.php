<?php

use App\Http\Controllers\Community\ChannelController;
use App\Models\CommunityMessage;
use App\Models\CommunityReaction;
use App\Models\CommunitySpace;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function channel(string $slug): CommunitySpace
{
    return CommunitySpace::query()->where('slug', $slug)->sole();
}

test('the community opens the first channel students can write in', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('community.index'))
        ->assertRedirect(route('community.channels.show', 'geral'));

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('community.index'))
        ->assertRedirect(route('community.channels.show', 'avisos'));
});

test('a channel shows its latest messages oldest first, with quotes and reactions', function () {
    $student = User::factory()->create();
    $general = channel('geral');
    $question = CommunityMessage::factory()->for($general, 'space')->for(User::factory()->create(['name' => 'Ana']))->create(['body' => 'Como exporto contatos?']);
    $answer = CommunityMessage::factory()->for($general, 'space')->for(User::factory()->admin()->create(['name' => 'Equipe']))
        ->create(['body' => '**Contatos** › Exportar', 'reply_to_id' => $question->id]);
    foreach ([$student, User::factory()->create()] as $user) {
        $reaction = new CommunityReaction;
        $reaction->emoji = '👍';
        $reaction->user()->associate($user);
        $answer->reactions()->save($reaction);
    }
    CommunityMessage::factory()->for(channel('apresente-se'), 'space')->create();

    $this->actingAs($student)
        ->get(route('community.channels.show', 'geral'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('community/Channel')
            ->where('channel.slug', 'geral')
            ->where('channel.messages_count', 2)
            ->where('channel.can_post', true)
            ->has('messages', 2)
            ->where('messages.0.id', $question->id)
            ->where('messages.1.author', ['name' => 'Equipe', 'is_team' => true, 'avatar' => null])
            ->where('messages.1.body_html', fn (string $html) => str_contains($html, '<strong>Contatos</strong>'))
            ->where('messages.1.reply_to', ['id' => $question->id, 'author' => 'Ana', 'excerpt' => 'Como exporto contatos?'])
            ->where('messages.1.reactions', [['emoji' => '👍', 'count' => 2, 'mine' => true]])
            ->where('messages.1.can.report', true)
            ->where('has_older', false)
            ->where('reactions', CommunityMessage::REACTIONS));
});

test('students read the announcements channel but cannot post there', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('community.channels.show', 'avisos'))
        ->assertInertia(fn (Assert $page) => $page->where('channel.can_post', false)->where('channel.staff_only', true));
});

test('older messages load page by page, and a search or a message link narrows the conversation', function () {
    $general = channel('geral');
    $messages = CommunityMessage::factory()->count(ChannelController::PAGE_SIZE + 5)->for($general, 'space')->create();
    CommunityMessage::factory()->for($general, 'space')->create(['body' => 'Integração com WhatsApp']);
    $student = User::factory()->create();

    $latest = fn (array $query) => $this->actingAs($student)->get(route('community.channels.show', ['geral', ...$query]))->viewData('page')['props'];

    $page = $latest([]);
    expect($page['messages'])->toHaveCount(ChannelController::PAGE_SIZE)
        ->and($page['has_older'])->toBeTrue();

    $older = $latest(['antes' => $page['messages'][0]['id']]);
    expect(collect($older['messages'])->pluck('id')->all())->toBe($messages->take(6)->pluck('id')->all())
        ->and($older['has_older'])->toBeFalse();

    expect(collect($latest(['busca' => 'whatsapp'])['messages'])->pluck('body_html')->all())
        ->toHaveCount(1)
        ->and($latest(['mensagem' => $messages[10]->id])['messages'][0]['id'])->toBe($messages[10]->id);
});
