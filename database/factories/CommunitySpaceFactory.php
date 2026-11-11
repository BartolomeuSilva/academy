<?php

namespace Database\Factories;

use App\Models\CommunitySpace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CommunitySpace>
 */
class CommunitySpaceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => Str::ucfirst($name),
            'slug' => Str::slug($name),
            'emoji' => '💬',
            'description' => fake()->sentence(),
            'staff_only' => false,
            'position' => fake()->numberBetween(10, 100),
        ];
    }

    /**
     * Indicate that only the team posts in the channel.
     */
    public function staffOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'staff_only' => true,
        ]);
    }
}
