<?php

namespace Database\Factories;

use App\Enums\AttachmentKind;
use App\Models\Lesson;
use App\Models\LessonAttachment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<LessonAttachment>
 */
class LessonAttachmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lesson_id' => Lesson::factory(),
            'kind' => AttachmentKind::Pdf,
            'path' => 'attachments/'.Str::uuid().'.pdf',
            'original_name' => fake()->word().'.pdf',
            'mime_type' => 'application/pdf',
            'size' => fake()->numberBetween(10_000, 5_000_000),
            'position' => 0,
        ];
    }

    /**
     * Indicate that the attachment is an image.
     */
    public function image(): static
    {
        return $this->state(fn (array $attributes) => [
            'kind' => AttachmentKind::Image,
            'path' => 'attachments/'.Str::uuid().'.png',
            'original_name' => fake()->word().'.png',
            'mime_type' => 'image/png',
        ]);
    }
}
