<?php

namespace Database\Factories;

use App\Enums\TrainingRunStatus;
use App\Models\TrainingRun;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TrainingRun>
 */
class TrainingRunFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'training_session_id' => TrainingSession::factory(),
            'started_by' => User::factory()->coach(),
            'status' => TrainingRunStatus::InProgress,
            'started_at' => now(),
        ];
    }
}
