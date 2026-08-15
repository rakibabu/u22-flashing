<?php

namespace Database\Factories;

use App\Enums\TrainingBlockType;
use App\Models\TrainingBlock;
use App\Models\TrainingSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainingBlock>
 */
class TrainingBlockFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'training_session_id' => TrainingSession::factory(),
            'block_type' => TrainingBlockType::Text,
            'position' => 1,
            'title' => fake()->sentence(3),
            'planned_duration_minutes' => 10,
        ];
    }
}
