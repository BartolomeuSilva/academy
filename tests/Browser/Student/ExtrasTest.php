<?php

use App\Models\CommunityMessage;
use App\Models\CommunitySpace;
use App\Models\Course;
use App\Models\LessonRating;
use App\Models\Module;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake();
});

test('the global search opens with the keyboard and goes to the chosen lesson', function () {
    $course = Course::factory()->published()->create(['title' => 'Vendas consultivas']);
    $lesson = visibleLesson(Module::factory()->for($course)->create(), ['title' => 'Objeções comuns']);
    CommunityMessage::factory()->create(['body' => 'Como lidar com objeções de preço?']);
    $this->actingAs(User::factory()->create());

    visit('/cursos')
        ->keys('@global-search-button', 'Control+k')
        ->assertVisible('@global-search-input')
        ->type('@global-search-input', 'objeções')
        ->assertSee('Objeções comuns')
        ->assertSee('Como lidar com objeções de preço?')
        ->assertNoAccessibilityIssues()
        ->keys('@global-search-input', 'Enter')
        ->assertPathIs("/cursos/{$course->slug}/aulas/{$lesson->slug}")
        ->assertNoJavaScriptErrors();
});

test('a student previews the chosen images before sending', function () {
    $this->actingAs(User::factory()->create());
    $fake = UploadedFile::fake()->image('erro.png', 800, 600);

    visit('/comunidade/geral')
        ->attach('@images-input', $fake->getPathname())
        ->assertSee('Remover imagens')
        ->assertPresent('@image-preview')
        ->click('Remover imagens')
        ->assertDontSee('Remover imagens')
        ->assertNoJavaScriptErrors();
});

// The browser test server does not nest `images[0]` uploads, so storing them is covered by MessageControllerTest.
test('the images of a message open larger', function () {
    $message = CommunityMessage::factory()->for(CommunitySpace::query()->where('slug', 'geral')->sole(), 'space')->create(['body' => 'Segue o print']);
    $message->attachImages([UploadedFile::fake()->image('erro.png', 800, 600)]);
    $this->actingAs(User::factory()->create());

    visit('/comunidade/geral')
        ->assertVisible('@post-images')
        ->click('[data-test="post-images"] button')
        ->assertVisible('img[alt="Imagem ampliada"]')
        ->assertNoAccessibilityIssues()
        ->assertNoJavaScriptErrors();
});

test('students turn reply e-mails off in their settings', function () {
    $student = User::factory()->create();
    $this->actingAs($student);

    visit('/settings/notifications')
        ->assertChecked('@notify-by-email-checkbox')
        ->uncheck('@notify-by-email-checkbox')
        ->click('@save-notification-settings-button')
        ->assertSee('Preferência salva.')
        ->assertNoAccessibilityIssues();

    expect($student->refresh()->notify_by_email)->toBeFalse();
});

test('the achievements page shows earned and locked medals in light and dark mode', function () {
    $student = User::factory()->create();
    LessonRating::factory()->for($student)->create();
    $this->actingAs($student);

    visit('/cursos')
        ->click('[data-sidebar="menu-button"]:has-text("Conquistas")')
        ->assertPathIs('/conquistas')
        ->assertSeeIn('@achievements-summary', '1')
        ->assertSee('1 aula avaliada')
        ->assertSee('Conquistada')
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues();

    visitInDarkTheme('/conquistas')->assertNoAccessibilityIssues();
});

// The browser test server garbles binary uploads and fake storage serves no files, so storing and showing
// the photo are covered by Settings/AvatarTest and checked on the running app.
test('a student with a profile photo can change or remove it in the settings', function () {
    $this->actingAs(User::factory()->create(['avatar_path' => 'avatars/eu.png']));

    visit('/settings/profile')
        ->assertSeeIn('@avatar-uploader', 'Trocar foto')
        ->assertSeeIn('@avatar-uploader', 'Remover')
        ->assertNoAccessibilityIssues()
        ->click('@remove-avatar-button')
        ->assertSee('Foto removida.')
        ->assertDontSeeIn('@avatar-uploader', 'Remover')
        ->assertNoJavaScriptErrors();
});
