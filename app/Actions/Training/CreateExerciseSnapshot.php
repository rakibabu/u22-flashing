<?php

namespace App\Actions\Training;

use App\Enums\TrainingCoach;
use App\Models\ExerciseLibraryItem;

class CreateExerciseSnapshot
{
    /** @return array<string, mixed> */
    public function handle(ExerciseLibraryItem $exercise): array
    {
        return [
            'name' => $exercise->name,
            'default_coach' => $exercise->default_coach?->value ?? TrainingCoach::Raki->value,
            'category' => $exercise->category,
            'objective' => $exercise->objective,
            'organization' => $exercise->organization,
            'execution' => $exercise->execution,
            'coaching_points' => $exercise->coaching_points ?? $this->lines($exercise->coaching_cues),
            'constraints' => $exercise->constraints ?? [],
            'regressions' => $exercise->regressions ?? [],
            'progressions' => $exercise->progressions ?? [],
            'default_duration_minutes' => $exercise->default_duration_minutes,
            'min_players' => $exercise->min_players,
            'max_players' => $exercise->max_players,
            'baskets_required' => $exercise->baskets_required,
            'materials' => $exercise->materials ?? [],
            'media_path' => $exercise->media_path,
            'media_type' => $exercise->media_type,
            'video_url' => $exercise->video_url,
            'external_url' => $exercise->external_url,
        ];
    }

    /** @return array<int, string> */
    private function lines(?string $value): array
    {
        return collect(preg_split('/\r?\n/', $value ?? '') ?: [])->filter()->values()->all();
    }
}
