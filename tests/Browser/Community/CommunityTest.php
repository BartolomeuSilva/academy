<?php

use App\Models\CommunityMessage;
use App\Models\CommunityReport;
use App\Models\CommunitySpace;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake();
});

test('students chat in a channel: one asks, another replies quoting and reacts, the author is notified', function () {
    $author = User::factory()->create(['name' => 'Ana Souza']);
    $helper = User::factory()->create(['name' => 'Bruno Lima']);
    $this->actingAs($author);

    visit('/comunidade')
        ->assertPathIs('/comunidade/geral')
        ->type('@message-input', 'Como exporto meus contatos?')
        ->keys('@message-input', 'Enter')
        ->assertSeeIn('@channel-messages', 'Como exporto meus contatos?')
        ->assertNoJavaScriptErrors();

    $question = CommunityMessage::query()->sole();
    $this->actingAs($helper);

    visit('/comunidade/geral')
        ->hover('@chat-message')
        ->click('@reply-message-button')
        ->assertSeeIn('@replying-to', 'Ana Souza')
        ->type('@message-input', 'Vá em Contatos › Exportar.')
        ->click('@send-message-button')
        ->assertSeeIn('@quoted-message', 'Como exporto meus contatos?')
        ->click("#mensagem-{$question->id} [data-test=\"add-reaction-button\"]")
        ->click('[aria-label="Reagir com 🙏"]')
        ->assertSeeIn('@reaction-chip', '1')
        ->assertNoJavaScriptErrors();

    expect(CommunityMessage::query()->latest('id')->first()->reply_to_id)->toBe($question->id)
        ->and($question->reactions()->count())->toBe(1);

    $this->actingAs($author);

    visit('/comunidade/geral')
        ->assertSeeIn('@notification-count', '1')
        ->click('@notification-bell')
        ->assertSee('Bruno Lima')
        ->click('@notification-item')
        ->assertPathIs('/comunidade/geral')
        ->assertSee('Vá em Contatos › Exportar.')
        ->assertMissing('@notification-count');
});

test('students read announcements but only the team writes there', function () {
    $announcements = CommunitySpace::query()->where('slug', 'avisos')->sole();
    CommunityMessage::factory()->for($announcements, 'space')->for(User::factory()->admin()->create())->create(['body' => 'Nova versão no ar!']);
    $this->actingAs(User::factory()->create());

    visit('/comunidade/avisos')
        ->assertSee('Nova versão no ar!')
        ->assertSee('EQUIPE')
        ->assertVisible('@staff-only-notice')
        ->assertMissing('@message-input');
});

test('a reported message reaches the team, who removes it', function () {
    $message = CommunityMessage::factory()
        ->for(CommunitySpace::query()->where('slug', 'geral')->sole(), 'space')
        ->create(['body' => 'Compre seguidores baratos!']);
    $this->actingAs(User::factory()->create());

    visit('/comunidade/geral')
        ->hover('@chat-message')
        ->click('@report-button')
        ->fill('#report-reason', 'Propaganda')
        ->click('@send-report-button')
        ->assertSee('Obrigado! A equipe vai analisar.');

    $this->actingAs(User::factory()->admin()->create());

    visit('/admin/comunidade/denuncias')
        ->assertSee('Compre seguidores baratos!')
        ->assertSee('Propaganda')
        ->click('@remove-reported-button')
        ->click('@confirm-dialog-button')
        ->assertSee('Mensagem removida.')
        ->assertSee('Nenhuma denúncia aberta');

    expect($message->refresh()->trashed())->toBeTrue()
        ->and(CommunityReport::query()->whereNull('resolved_at')->count())->toBe(0);
});

test('the team creates a channel for a product and switches it to team-only and back', function () {
    $this->actingAs(User::factory()->admin()->create());

    visit('/admin/comunidade/espacos')
        ->fill('#new-space-emoji', '📝')
        ->fill('#new-space-name', 'TupiForm')
        ->fill('#new-space-description', 'Dúvidas sobre formulários')
        ->click('@create-space-button')
        ->assertSee('Canal criado.');

    $channel = CommunitySpace::query()->where('slug', 'tupiform')->sole();
    expect($channel)->emoji->toBe('📝')->staff_only->toBeFalse();

    $card = '[data-test="admin-community-space"]:has-text("TupiForm")';

    visit('/admin/comunidade/espacos')
        ->check("{$card} input[type=checkbox]")
        ->click("{$card} button:has-text(\"Salvar\")")
        ->assertSee('Canal atualizado.');
    expect($channel->refresh()->staff_only)->toBeTrue();

    visit('/admin/comunidade/espacos')
        ->uncheck("{$card} input[type=checkbox]")
        ->click("{$card} button:has-text(\"Salvar\")")
        ->assertSee('Canal atualizado.');
    expect($channel->refresh()->staff_only)->toBeFalse();

    $this->actingAs(User::factory()->create());

    visit('/comunidade/tupiform')
        ->assertSee('Dúvidas sobre formulários')
        ->assertVisible('@message-input');
});

test('community pages have no accessibility issues in light and dark mode', function () {
    $general = CommunitySpace::query()->where('slug', 'geral')->sole();
    $question = CommunityMessage::factory()->for($general, 'space')->create(['body' => 'Primeira dúvida']);
    $answer = CommunityMessage::factory()->for($general, 'space')->for(User::factory()->admin()->create())
        ->create(['body' => 'Resposta da equipe', 'reply_to_id' => $question->id]);
    $answer->attachImages([UploadedFile::fake()->image('print.png')]);
    $this->actingAs(User::factory()->create());

    $urls = ['/comunidade/geral', '/comunidade/avisos', '/comunidade/aulas'];

    visit($urls)->assertNoSmoke()->assertNoAccessibilityIssues();

    foreach ($urls as $url) {
        visitInDarkTheme($url)->assertNoAccessibilityIssues();
    }

    $this->actingAs(User::factory()->admin()->create());
    CommunityReport::factory()->create(['reportable_id' => $question->id]);

    visit(['/admin/comunidade/espacos', '/admin/comunidade/denuncias'])->assertNoSmoke()->assertNoAccessibilityIssues();
});

test('phones reach the community from the bottom bar and switch channels', function () {
    CommunityMessage::factory()->for(CommunitySpace::query()->where('slug', 'apresente-se')->sole(), 'space')->create(['body' => 'Oi, sou a Carla!']);
    $this->actingAs(User::factory()->create());

    visit('/cursos')
        ->on()->mobile()
        ->click('[data-test="bottom-nav"] >> text=Comunidade')
        ->assertPathIs('/comunidade/geral')
        ->click('[data-test="community-channel-link"]:has-text("Apresente-se")')
        ->assertSee('Oi, sou a Carla!')
        ->assertVisible('@message-input')
        ->assertNoSmoke();
});
