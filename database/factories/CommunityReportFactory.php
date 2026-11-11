<?php

namespace Database\Factories;

use App\Models\CommunityMessage;
use App\Models\CommunityReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommunityReport>
 */
class CommunityReportFactory extends Factory
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
            'reportable_type' => 'community_message',
            'reportable_id' => CommunityMessage::factory(),
            'reason' => fake()->sentence(),
        ];
    }
}
