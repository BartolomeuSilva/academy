<?php

use App\Enums\AttachmentKind;
use App\Models\Lesson;
use App\Models\LessonAttachment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake();
});

test('uploads images and pdfs after the existing attachments', function () {
    $lesson = Lesson::factory()->create();
    LessonAttachment::factory()->for($lesson)->create(['position' => 1]);

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.lessons.attachments.store', $lesson), [
            'files' => [
                UploadedFile::fake()->image('diagrama.png'),
                UploadedFile::fake()->create('apostila.pdf', 200, 'application/pdf'),
            ],
        ])
        ->assertRedirect();

    $image = LessonAttachment::query()->where('original_name', 'diagrama.png')->sole();
    $pdf = LessonAttachment::query()->where('original_name', 'apostila.pdf')->sole();
    expect($image)->kind->toBe(AttachmentKind::Image)->position->toBe(2);
    expect($pdf)->kind->toBe(AttachmentKind::Pdf)->position->toBe(3);
    Storage::assertExists([$image->path, $pdf->path]);
});

test('rejects files that are not images or pdfs', function () {
    $lesson = Lesson::factory()->create();

    $response = $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.lessons.attachments.store', $lesson), [
            'files' => [UploadedFile::fake()->create('script.sh', 1, 'text/x-shellscript')],
        ]);

    $response->assertSessionHasErrors(['files.0' => 'O campo arquivo deve ser um arquivo do tipo: jpg, jpeg, png, webp, pdf.']);
    $this->assertDatabaseCount('lesson_attachments', 0);
});

test('rejects files larger than 50 MB', function () {
    $lesson = Lesson::factory()->create();

    $response = $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.lessons.attachments.store', $lesson), [
            'files' => [UploadedFile::fake()->create('grande.pdf', 51_201, 'application/pdf')],
        ]);

    $response->assertSessionHasErrors(['files.0' => 'O campo arquivo não pode ser maior que 51200 kilobytes.']);
});

test('renames an attachment', function () {
    $attachment = LessonAttachment::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.attachments.update', $attachment), ['original_name' => 'Manual do colaborador.pdf'])
        ->assertRedirect();

    expect($attachment->fresh()->original_name)->toBe('Manual do colaborador.pdf');
});

test('deletes an attachment and its file', function () {
    $attachment = LessonAttachment::factory()->create();
    Storage::put($attachment->path, 'pdf');

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('admin.attachments.destroy', $attachment))
        ->assertRedirect();

    $this->assertModelMissing($attachment);
    Storage::assertMissing($attachment->path);
});

test('saves the attachment order', function () {
    $lesson = Lesson::factory()->create();
    [$first, $second] = LessonAttachment::factory()->count(2)->for($lesson)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.lessons.attachments.order', $lesson), ['ids' => [$second->id, $first->id]])
        ->assertRedirect();

    expect([$second->fresh()->position, $first->fresh()->position])->toBe([0, 1]);
});
