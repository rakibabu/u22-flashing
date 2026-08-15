<?php

namespace Database\Factories;

use App\Models\Player;
use App\Models\TrainingAttendance;
use App\Models\TrainingRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainingAttendance>
 */
class TrainingAttendanceFactory extends Factory
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
            'player_id' => Player::factory(),
            'status' => 'unknown',
        ];
    }
}
