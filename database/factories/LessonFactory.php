<?php

namespace Database\Factories;

use App\Enums\PublicationStatus;
use App\Models\Lesson;
use App\Models\Module;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Lesson>
 */
class LessonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'module_id' => Module::factory(),
            'course_id' => fn (array $attributes) => Module::query()->whereKey($attributes['module_id'])->value('course_id'),
            'title' => $title,
            'slug' => Str::slug($title),
            'description' => fake()->paragraph(),
            'status' => PublicationStatus::Draft,
            'position' => 0,
        ];
    }
}
