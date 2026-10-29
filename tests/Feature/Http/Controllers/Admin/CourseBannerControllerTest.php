<?php

use App\Models\Course;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake();
});

test('uploads a banner and removes the previous one', function () {
    $course = Course::factory()->featured()->create();
    $previousPath = $course->banner_path;
    Storage::put($previousPath, 'antigo');

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.courses.banner.store', [$course, 'computador']), [
            'banner' => UploadedFile::fake()->image('banner.jpg', 1920, 820),
        ])
        ->assertRedirect()
        ->assertInertiaFlash('toast.message', 'Banner atualizado.');

    $course->refresh();
    expect($course->banner_path)->toStartWith('banners/')->not->toBe($previousPath);
    Storage::assertExists($course->banner_path);
    Storage::assertMissing($previousPath);
});

test('rejects banners that are not images', function () {
    $course = Course::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.courses.banner.store', [$course, 'computador']), [
            'banner' => UploadedFile::fake()->create('banner.pdf', 100, 'application/pdf'),
        ])
        ->assertSessionHasErrors(['banner' => 'O campo banner deve ser uma imagem.']);

    expect($course->fresh()->banner_path)->toBeNull();
});

test('removing the banner also takes the course out of the spotlight', function () {
    $course = Course::factory()->featured()->create();
    Storage::put($course->banner_path, 'banner');
    $path = $course->banner_path;

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('admin.courses.banner.destroy', [$course, 'computador']))
        ->assertRedirect();

    expect($course->fresh())
        ->banner_path->toBeNull()
        ->featured_at->toBeNull();
    Storage::assertMissing($path);
});

test('the phone banner is stored apart from the desktop one', function () {
    $course = Course::factory()->featured()->create();
    $desktopPath = $course->banner_path;

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.courses.banner.store', [$course, 'celular']), [
            'banner' => UploadedFile::fake()->image('banner.jpg', 1080, 1350),
        ])
        ->assertRedirect();

    $course->refresh();
    expect($course->banner_path)->toBe($desktopPath)
        ->and($course->banner_mobile_path)->toStartWith('banners/');
    Storage::assertExists($course->banner_mobile_path);
});

test('removing the phone banner keeps the course featured', function () {
    $course = Course::factory()->featured()->create(['banner_mobile_path' => 'banners/celular.jpg']);
    Storage::put('banners/celular.jpg', 'banner');

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('admin.courses.banner.destroy', [$course, 'celular']))
        ->assertRedirect();

    expect($course->fresh())
        ->banner_mobile_path->toBeNull()
        ->featured_at->not->toBeNull();
    Storage::assertMissing('banners/celular.jpg');
});

test('only the desktop and phone banners exist', function () {
    $course = Course::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post("/admin/cursos/{$course->id}/banner/tablet", ['banner' => UploadedFile::fake()->image('b.jpg')])
        ->assertNotFound();
});

test('featuring a course replaces the current featured one', function () {
    $current = Course::factory()->featured()->create();
    $course = Course::factory()->create(['banner_path' => 'banners/novo.jpg']);

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.courses.feature.store', $course))
        ->assertRedirect()
        ->assertInertiaFlash('toast.message', 'Curso em destaque na vitrine.');

    expect($course->fresh()->featured_at)->not->toBeNull()
        ->and($current->fresh()->featured_at)->toBeNull();
});

test('a course needs a banner to be featured', function () {
    $course = Course::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.courses.feature.store', $course))
        ->assertSessionHasErrors(['banner' => 'Envie o banner antes de destacar o curso.']);

    expect($course->fresh()->featured_at)->toBeNull();
});

test('a course can leave the spotlight', function () {
    $course = Course::factory()->featured()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('admin.courses.feature.destroy', $course))
        ->assertInertiaFlash('toast.message', 'Curso removido do destaque.');

    expect($course->fresh()->featured_at)->toBeNull();
});

test('students can not manage banners or the spotlight', function () {
    $course = Course::factory()->create(['banner_path' => 'banners/x.jpg']);
    $student = User::factory()->create();

    $this->actingAs($student)
        ->post(route('admin.courses.banner.store', [$course, 'computador']), ['banner' => UploadedFile::fake()->image('b.jpg')])
        ->assertForbidden();
    $this->actingAs($student)->post(route('admin.courses.feature.store', $course))->assertForbidden();

    expect($course->fresh()->featured_at)->toBeNull();
});
