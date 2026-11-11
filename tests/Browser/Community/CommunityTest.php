<?php

use App\Models\CommunityReply;
use App\Models\CommunityReport;
use App\Models\CommunitySpace;
use App\Models\CommunityTopic;
use App\Models\User;

test('a student asks, another answers, and the author is notified and marks the solution', function () {
    $author = User::factory()->create(['name' => 'Ana Souza']);
    $helper = User::factory()->create(['name' => 'Bruno Lima']);
    $this->actingAs($author);

    visit('/comunidade')
        ->assertSee('Geral')
        ->click('@new-topic-link')
        ->select('@topic-space-select', (string) CommunitySpace::query()->where('slug', 'geral')->value('id'))
        ->fill('@topic-title-input', 'Como exporto meus contatos?')
        ->fill('@topic-body-input', 'Preciso de uma **planilha** com todos.')
        ->click('@publish-topic-button')
        ->assertSee('Como exporto meus contatos?')
        ->assertSee('planilha')
        ->assertNoJavaScriptErrors();

    $topic = CommunityTopic::query()->sole();
    $this->actingAs($helper);

    visit("/comunidade/topicos/{$topic->id}")
        ->fill('@reply-body-input', 'Vá em Contatos > Exportar.')
        ->click('@send-reply-button')
        ->assertSee('Vá em Contatos > Exportar.')
        ->assertSee('1 resposta');

    $this->actingAs($author);

    visit('/comunidade')
        ->assertSeeIn('@notification-count', '1')
        ->click('@notification-bell')
        ->assertSee('Bruno Lima')
        ->click('@notification-item')
        ->assertPathIs("/comunidade/topicos/{$topic->id}")
        ->click('@toggle-solution-button')
        ->assertSee('Resposta que resolveu')
        ->assertMissing('@notification-count')
        ->assertNoJavaScriptErrors();

    expect($topic->refresh()->solution_reply_id)->toBe(CommunityReply::query()->sole()->id);
});

test('a reported reply reaches the team, who removes it', function () {
    $reply = CommunityReply::factory()->create(['body' => 'Compre seguidores baratos!']);
    $this->actingAs(User::factory()->create());

    visit("/comunidade/topicos/{$reply->topic_id}")
        ->click('[data-test="community-reply"] [data-test="report-button"]')
        ->fill('#report-reason', 'Propaganda')
        ->click('@send-report-button')
        ->assertSee('Obrigado! A equipe vai analisar.');

    $this->actingAs(User::factory()->admin()->create());

    visit('/admin/comunidade/denuncias')
        ->assertSee('Compre seguidores baratos!')
        ->assertSee('Propaganda')
        ->click('@remove-reported-button')
        ->click('@confirm-dialog-button')
        ->assertSee('Conteúdo removido.')
        ->assertSee('Nenhuma denúncia aberta');

    expect($reply->refresh()->trashed())->toBeTrue()
        ->and(CommunityReport::query()->whereNull('resolved_at')->count())->toBe(0);
});

test('the team creates a space for a product and students post in it', function () {
    $this->actingAs(User::factory()->admin()->create());

    visit('/admin/comunidade/espacos')
        ->fill('#new-space-name', 'ZendFlow')
        ->fill('#new-space-description', 'Dúvidas sobre o CRM')
        ->click('@create-space-button')
        ->assertSee('Espaço criado.');

    expect(CommunitySpace::query()->where('slug', 'zendflow')->sole()->staff_only)->toBeFalse();

    $this->actingAs(User::factory()->create());

    visit('/comunidade?espaco=zendflow')
        ->assertSee('Dúvidas sobre o CRM')
        ->assertVisible('@new-topic-link');

    visit('/comunidade?espaco=avisos')
        ->assertMissing('@new-topic-link');
});

test('community pages have no accessibility issues in light and dark mode', function () {
    $student = User::factory()->create();
    $topic = CommunityTopic::factory()->pinned()->create(['title' => 'Boas-vindas à comunidade']);
    CommunityReply::factory()->for($topic, 'topic')->for(User::factory()->admin()->create())->create();
    $this->actingAs($student);

    $urls = ['/comunidade', '/comunidade/topicos/criar', "/comunidade/topicos/{$topic->id}", '/comunidade/aulas'];

    visit($urls)->assertNoSmoke()->assertNoAccessibilityIssues();

    foreach ($urls as $url) {
        visitInDarkTheme($url)->assertNoAccessibilityIssues();
    }

    $this->actingAs(User::factory()->admin()->create());
    CommunityReport::factory()->create(['reportable_id' => $topic->id]);

    visit(['/admin/comunidade/espacos', '/admin/comunidade/denuncias'])->assertNoSmoke()->assertNoAccessibilityIssues();
});

test('phones reach the community from the bottom bar', function () {
    CommunityTopic::factory()->create(['title' => 'Dúvida no celular']);
    $this->actingAs(User::factory()->create());

    visit('/cursos')
        ->on()->mobile()
        ->click('[data-test="bottom-nav"] >> text=Comunidade')
        ->assertPathIs('/comunidade')
        ->assertSee('Dúvida no celular')
        ->assertNoSmoke();
});

test('the team switches a space to team-only and back', function () {
    $space = CommunitySpace::factory()->create(['name' => 'Novidades do produto']);
    $this->actingAs(User::factory()->admin()->create());

    $card = '[data-test="admin-community-space"]:has-text("Novidades do produto")';

    visit('/admin/comunidade/espacos')
        ->check("{$card} input[type=checkbox]")
        ->click("{$card} button:has-text(\"Salvar\")")
        ->assertSee('Espaço atualizado.');

    expect($space->refresh()->staff_only)->toBeTrue();

    visit('/admin/comunidade/espacos')
        ->uncheck("{$card} input[type=checkbox]")
        ->click("{$card} button:has-text(\"Salvar\")")
        ->assertSee('Espaço atualizado.');

    expect($space->refresh()->staff_only)->toBeFalse();
});
