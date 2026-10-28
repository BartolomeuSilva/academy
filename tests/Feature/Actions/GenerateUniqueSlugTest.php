<?php

use App\Actions\GenerateUniqueSlug;
use App\Models\Course;

test('it appends the next free suffix when the slug is taken', function () {
    Course::factory()->create(['slug' => 'seguranca-da-informacao']);
    Course::factory()->create(['slug' => 'seguranca-da-informacao-2']);

    $slug = (new GenerateUniqueSlug)->handle(Course::query(), 'Segurança da Informação');

    expect($slug)->toBe('seguranca-da-informacao-3');
});

test('it ignores the record being updated', function () {
    $course = Course::factory()->create(['slug' => 'onboarding']);

    $slug = (new GenerateUniqueSlug)->handle(Course::query(), 'Onboarding', $course->id);

    expect($slug)->toBe('onboarding');
});

test('it falls back to a generic slug when the value has no letters or numbers', function () {
    $slug = (new GenerateUniqueSlug)->handle(Course::query(), '!!!');

    expect($slug)->toBe('item');
});
