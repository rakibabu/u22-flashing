<?php

namespace Database\Factories;

use App\Enums\TrainingStatus;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainingSession>
 */
class TrainingSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'created_by' => User::factory()->coach(),
            'title' => fake()->sentence(4),
            'scheduled_at' => now()->addWeek(),
            'planned_duration_minutes' => 120,
            'expected_player_count' => 12,
            'available_baskets' => 2,
            'status' => TrainingStatus::Draft,
        ];
    }
}
