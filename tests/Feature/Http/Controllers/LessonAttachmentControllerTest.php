<?php

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonAttachment;
use App\Models\Module;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake();
});

test('attachments open inline or download with their original name', function (array $query, string $disposition) {
    Storage::buildTemporaryUrlsUsing(fn (string $path, DateTimeInterface $expiration, array $options) => "https://s3.test/{$path}?disposition=".urlencode($options['ResponseContentDisposition']));
    $lesson = visibleLesson(Module::factory()->for(Course::factory()->published())->create());
    $attachment = LessonAttachment::factory()->for($lesson)->create(['original_name' => 'Manual.pdf']);

    $response = $this->actingAs(User::factory()->create())
        ->get(route('attachments.show', [$attachment, ...$query]));

    $response->assertRedirect();
    expect(urldecode((string) $response->headers->get('Location')))
        ->toContain($attachment->path)
        ->toContain("{$disposition}; filename=\"Manual.pdf\"");
})->with([
    'view' => [[], 'inline'],
    'download' => [['download' => 1], 'attachment'],
]);

test('students can not open attachments of lessons they can not watch', function () {
    $attachment = LessonAttachment::factory()
        ->for(Lesson::factory()->for(Module::factory()->for(Course::factory()->published())))
        ->create();

    $this->actingAs(User::factory()->create())
        ->get(route('attachments.show', $attachment))
        ->assertNotFound();
});

test('guests are redirected to login', function () {
    $this->get(route('attachments.show', LessonAttachment::factory()->create()))
        ->assertRedirect(route('login'));
});
