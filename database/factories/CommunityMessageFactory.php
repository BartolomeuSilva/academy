<?php

namespace Database\Factories;

use App\Models\CommunityMessage;
use App\Models\CommunitySpace;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommunityMessage>
 */
class CommunityMessageFactory extends Factory
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
            'body' => fake()->sentence(12),
        ];
    }
}
