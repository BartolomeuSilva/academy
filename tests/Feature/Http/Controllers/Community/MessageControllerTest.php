<?php

use App\Jobs\DeleteStoragePaths;
use App\Models\CommunityImage;
use App\Models\CommunityMessage;
use App\Models\CommunityReaction;
use App\Models\CommunityReport;
use App\Models\CommunitySpace;
use App\Models\User;
use App\Notifications\CommunityMessageReplied;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake();
});

function generalChannel(): CommunitySpace
{
    return CommunitySpace::query()->where('slug', 'geral')->sole();
}

test('a student posts a message with images in a channel', function () {
    $student = User::factory()->create();

    $this->actingAs($student)
        ->post(route('community.messages.store', 'geral'), [
            'body' => '  Olá, pessoal!  ',
            'images' => [UploadedFile::fake()->image('print.png')],
        ])
        ->assertRedirect(route('community.channels.show', 'geral'));

    $message = CommunityMessage::query()->sole();
    expect($message)->body->toBe('Olá, pessoal!')->user_id->toBe($student->id)->space_id->toBe(generalChannel()->id)
        ->and($message->images()->count())->toBe(1);
    Storage::assertExists($message->images()->value('path'));
});

test('an image can be sent without text, but an empty message cannot', function () {
    $student = User::factory()->create();

    $this->actingAs($student)
        ->post(route('community.messages.store', 'geral'), ['body' => ''])
        ->assertSessionHasErrors(['body' => 'Escreva uma mensagem ou envie uma imagem.']);

    $this->actingAs($student)
        ->post(route('community.messages.store', 'geral'), ['images' => [UploadedFile::fake()->image('print.png')]])
        ->assertSessionHasNoErrors();

    expect(CommunityMessage::query()->sole()->body)->toBe('');
});

test('messages accept at most four images of up to 5 MB', function (array $images) {
    $this->actingAs(User::factory()->create())
        ->post(route('community.messages.store', 'geral'), ['body' => 'Prints', 'images' => $images])
        ->assertSessionHasErrors();

    expect(CommunityMessage::query()->count())->toBe(0);
})->with([
    'five images' => [fn () => array_map(fn ($i) => UploadedFile::fake()->image("{$i}.png"), range(1, 5))],
    'not an image' => [fn () => [UploadedFile::fake()->create('planilha.xlsx', 10)]],
    'too large' => [fn () => [UploadedFile::fake()->image('grande.png')->size(CommunityImage::MAX_KILOBYTES + 1)]],
]);

test('only the team posts in team-only channels', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('community.messages.store', 'avisos'), ['body' => 'Oi'])
        ->assertForbidden();
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('community.messages.store', 'avisos'), ['body' => 'Nova versão no ar!'])
        ->assertRedirect();

    expect(CommunityMessage::query()->count())->toBe(1);
});

test('replying quotes a message of the same channel and tells its author', function () {
    Notification::fake();
    $question = CommunityMessage::factory()->for(generalChannel(), 'space')->create();
    $elsewhere = CommunityMessage::factory()->create();
    $student = User::factory()->create();

    $this->actingAs($student)
        ->post(route('community.messages.store', 'geral'), ['body' => 'Resposta', 'reply_to_id' => $elsewhere->id])
        ->assertSessionHasErrors('reply_to_id');

    $this->actingAs($student)->post(route('community.messages.store', 'geral'), ['body' => 'Resposta', 'reply_to_id' => $question->id]);
    $this->actingAs($question->user)->post(route('community.messages.store', 'geral'), ['body' => 'Complemento', 'reply_to_id' => $question->id]);

    Notification::assertSentToTimes($question->user, CommunityMessageReplied::class, 1);
});

test('only the author edits a message', function () {
    $message = CommunityMessage::factory()->for(generalChannel(), 'space')->create(['body' => 'Antes']);

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('community.messages.update', ['geral', $message]), ['body' => 'Depois'])
        ->assertForbidden();
    $this->actingAs($message->user)->put(route('community.messages.update', ['geral', $message]), ['body' => 'Depois']);

    expect($message->refresh())->body->toBe('Depois')->edited_at->not->toBeNull();
});

test('authors and admins delete messages and their images', function () {
    Bus::fake([DeleteStoragePaths::class]);
    $message = CommunityMessage::factory()->for(generalChannel(), 'space')->create();
    $message->attachImages([UploadedFile::fake()->image('print.png')]);

    $this->actingAs(User::factory()->create())->delete(route('community.messages.destroy', ['geral', $message]))->assertForbidden();
    $this->actingAs(User::factory()->admin()->create())->delete(route('community.messages.destroy', ['geral', $message]));

    expect($message->refresh()->trashed())->toBeTrue()
        ->and(CommunityImage::query()->count())->toBe(0);
    Bus::assertDispatched(DeleteStoragePaths::class);
});

test('messages are only reached through their own channel', function () {
    $message = CommunityMessage::factory()->for(CommunitySpace::query()->where('slug', 'apresente-se')->sole(), 'space')->create();

    $this->actingAs($message->user)
        ->delete(route('community.messages.destroy', ['geral', $message]))
        ->assertNotFound();
});

test('a reaction is added once and removed when chosen again', function () {
    $message = CommunityMessage::factory()->for(generalChannel(), 'space')->create();
    $student = User::factory()->create();
    $react = fn (string $emoji) => $this->actingAs($student)->post(route('community.messages.reactions.store', ['geral', $message]), ['emoji' => $emoji]);

    $react('🎉');
    expect(CommunityReaction::query()->sole())->emoji->toBe('🎉')->user_id->toBe($student->id);

    $react('🎉');
    expect(CommunityReaction::query()->count())->toBe(0);

    $react('🍕')->assertSessionHasErrors('emoji');
});

test('a student reports a message, and reporting again reopens the same report', function () {
    $message = CommunityMessage::factory()->for(generalChannel(), 'space')->create();
    $student = User::factory()->create();

    $this->actingAs($message->user)->post(route('community.messages.report', ['geral', $message]))->assertForbidden();

    $this->actingAs($student)
        ->post(route('community.messages.report', ['geral', $message]), ['reason' => 'Propaganda'])
        ->assertInertiaFlash('toast.message', 'Obrigado! A equipe vai analisar.');
    CommunityReport::query()->update(['resolved_at' => now()]);
    $this->actingAs($student)->post(route('community.messages.report', ['geral', $message]), ['reason' => 'De novo']);

    expect(CommunityReport::query()->sole())
        ->reportable_type->toBe('community_message')
        ->reason->toBe('De novo')
        ->resolved_at->toBeNull();
});
