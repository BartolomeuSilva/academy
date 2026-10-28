<?php

use App\Models\Course;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake();
});

test('uploads a cover and removes the previous one', function () {
    $course = Course::factory()->withCover()->create();
    $previousPath = $course->cover_path;
    Storage::put($previousPath, 'antiga');

    $response = $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.courses.cover.store', $course), [
            'cover' => UploadedFile::fake()->image('capa.jpg', 1280, 720),
        ]);

    $response->assertRedirect();
    $course->refresh();
    expect($course->cover_path)->toStartWith('covers/')->not->toBe($previousPath);
    Storage::assertExists($course->cover_path);
    Storage::assertMissing($previousPath);
});

test('rejects files that are not images', function () {
    $course = Course::factory()->create();

    $response = $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.courses.cover.store', $course), [
            'cover' => UploadedFile::fake()->create('capa.pdf', 100, 'application/pdf'),
        ]);

    $response->assertSessionHasErrors(['cover' => 'O campo capa deve ser uma imagem.']);
    expect($course->fresh()->cover_path)->toBeNull();
});

test('removes the cover', function () {
    $course = Course::factory()->withCover()->create();
    $path = $course->cover_path;
    Storage::put($path, 'capa');

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('admin.courses.cover.destroy', $course))
        ->assertRedirect();

    expect($course->fresh()->cover_path)->toBeNull();
    Storage::assertMissing($path);
});
