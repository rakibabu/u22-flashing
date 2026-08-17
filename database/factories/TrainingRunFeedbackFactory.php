<?php

namespace Database\Factories;

use App\Models\TrainingRun;
use App\Models\TrainingRunFeedback;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainingRunFeedback>
 */
class TrainingRunFeedbackFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'training_run_id' => TrainingRun::factory(),
            'author_name' => fake()->name(),
            'feedback' => fake()->paragraph(),
        ];
    }
}
