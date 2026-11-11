<?php

namespace Database\Factories;

use App\Models\Lesson;
use App\Models\LessonRating;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LessonRating>
 */
class LessonRatingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'lesson_id' => Lesson::factory(),
            'stars' => fake()->numberBetween(1, 5),
            'feedback' => null,
        ];
    }
}
