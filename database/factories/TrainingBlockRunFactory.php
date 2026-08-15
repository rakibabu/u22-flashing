<?php

namespace Database\Factories;

use App\Enums\TrainingBlockRunStatus;
use App\Models\TrainingBlock;
use App\Models\TrainingBlockRun;
use App\Models\TrainingRun;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TrainingBlockRun>
 */
class TrainingBlockRunFactory extends Factory
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
            'training_run_id' => TrainingRun::factory(),
            'training_block_id' => TrainingBlock::factory(),
            'status' => TrainingBlockRunStatus::Pending,
        ];
    }
}
