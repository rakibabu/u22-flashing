<?php

namespace App\Actions\Training;

use App\Models\TrainingSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReorderTrainingBlocks
{
    /** @param array<int, int> $blockIds */
    public function handle(TrainingSession $training, array $blockIds): void
    {
        DB::transaction(function () use ($training, $blockIds): void {
            $existingIds = $training->blocks()->lockForUpdate()->pluck('id')->all();
            $sortedExistingIds = $existingIds;
            $sortedBlockIds = $blockIds;
            sort($sortedExistingIds);
            sort($sortedBlockIds);
            if ($sortedExistingIds !== $sortedBlockIds) {
                throw ValidationException::withMessages(['blocks' => 'De blokvolgorde is niet geldig.']);
            }

            foreach ($blockIds as $position => $blockId) {
                $training->blocks()->whereKey($blockId)->update(['position' => $position + 1000]);
            }
            foreach ($blockIds as $position => $blockId) {
                $training->blocks()->whereKey($blockId)->update(['position' => $position + 1]);
            }
        });
    }
}
