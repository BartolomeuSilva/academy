<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
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
            'user_id' => User::factory(),
            'parent_id' => null,
            'body' => fake()->paragraph(),
            'edited_at' => null,
        ];
    }

    /**
     * Indicate that the comment replies to the given root comment.
     */
    public function replyTo(Comment $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'lesson_id' => $parent->lesson_id,
            'parent_id' => $parent->id,
        ]);
    }
}
