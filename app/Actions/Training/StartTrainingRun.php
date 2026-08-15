<?php

namespace App\Actions\Training;

use App\Enums\TrainingBlockRunStatus;
use App\Enums\TrainingRunStatus;
use App\Enums\TrainingStatus;
use App\Models\TrainingRun;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class StartTrainingRun
{
    public function handle(TrainingSession $training, User $coach): TrainingRun
    {
        return DB::transaction(function () use ($training, $coach): TrainingRun {
            $activeRun = $training->runs()->whereIn('status', [TrainingRunStatus::InProgress, TrainingRunStatus::Paused])->latest()->first();
            if ($activeRun) {
                return $activeRun;
            }

            $firstBlock = $training->blocks()->firstOrFail();
            $run = $training->runs()->create([
                'started_by' => $coach->id,
                'current_training_block_id' => $firstBlock->id,
                'status' => TrainingRunStatus::InProgress,
                'started_at' => now(),
            ]);
            $training->update(['status' => TrainingStatus::InProgress]);
            $run->blockRuns()->create(['training_block_id' => $firstBlock->id, 'status' => TrainingBlockRunStatus::InProgress, 'started_at' => now()]);

            return $run;
        });
    }
}
