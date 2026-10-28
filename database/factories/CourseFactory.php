<?php

namespace Database\Factories;

use App\Enums\PublicationStatus;
use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'summary' => fake()->sentence(12),
            'description' => fake()->paragraph(),
            'cover_path' => null,
            'status' => PublicationStatus::Draft,
            'published_at' => null,
            'position' => 0,
        ];
    }

    /**
     * Indicate that the course is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PublicationStatus::Published,
            'published_at' => now(),
        ]);
    }

    /**
     * Indicate that the course has a cover image.
     */
    public function withCover(): static
    {
        return $this->state(fn (array $attributes) => [
            'cover_path' => 'covers/'.Str::uuid().'.jpg',
        ]);
    }
}
