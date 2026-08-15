<?php

namespace App\Actions\Training;

use App\Enums\TrainingStatus;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DuplicateTrainingSession
{
    public function handle(TrainingSession $source, User $coach, ?string $scheduledAt = null): TrainingSession
    {
        return DB::transaction(function () use ($source, $coach, $scheduledAt): TrainingSession {
            $copy = $source->replicate(['published_at', 'completed_at']);
            $copy->fill([
                'created_by' => $coach->id,
                'source_training_session_id' => $source->id,
                'status' => TrainingStatus::Draft,
                'scheduled_at' => $scheduledAt,
                'title' => $source->title.' (kopie)',
            ]);
            $copy->save();

            $source->blocks()->get()->each(function ($block) use ($copy): void {
                $newBlock = $block->replicate();
                $newBlock->training_session_id = $copy->id;
                $newBlock->save();
            });

            return $copy;
        });
    }
}
