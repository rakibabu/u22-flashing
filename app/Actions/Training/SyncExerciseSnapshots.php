<?php

namespace App\Actions\Training;

use App\Enums\TrainingStatus;
use App\Models\ExerciseLibraryItem;
use App\Models\TrainingBlock;

class SyncExerciseSnapshots
{
    public function __construct(private readonly CreateExerciseSnapshot $createExerciseSnapshot) {}

    public function handle(ExerciseLibraryItem $exercise): void
    {
        TrainingBlock::query()
            ->where('exercise_library_item_id', $exercise->id)
            ->whereHas('trainingSession', fn ($query) => $query->whereIn('status', [TrainingStatus::Draft, TrainingStatus::Published]))
            ->update(['exercise_snapshot' => $this->createExerciseSnapshot->handle($exercise)]);
    }
}
