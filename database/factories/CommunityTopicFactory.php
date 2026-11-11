<?php

namespace Database\Factories;

use App\Models\CommunitySpace;
use App\Models\CommunityTopic;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommunityTopic>
 */
class CommunityTopicFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'space_id' => CommunitySpace::factory(),
            'user_id' => User::factory(),
            'title' => rtrim(fake()->sentence(6), '.').'?',
            'body' => fake()->paragraph(),
            'last_activity_at' => now(),
        ];
    }

    /**
     * Indicate that the team pinned the topic.
     */
    public function pinned(): static
    {
        return $this->state(fn (array $attributes) => [
            'pinned_at' => now(),
        ]);
    }
}
