<?php

namespace App\Actions\ExerciseLibrary;

use App\Models\ExerciseLibraryItem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class ExportExerciseLibrary
{
    public function handle(): string
    {
        $filename = 'exercise-library-'.now()->format('Ymd-His').'-'.Str::random(8).'.zip';
        $path = Storage::disk('local')->path('exercise-exports/'.$filename);

        Storage::disk('local')->makeDirectory('exercise-exports');

        $archive = new ZipArchive;

        if ($archive->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Het exportbestand kon niet worden aangemaakt.');
        }

        try {
            $exercises = ExerciseLibraryItem::active()->orderBy('id')->get();
            $records = $exercises->map(function (ExerciseLibraryItem $exercise) use ($archive): array {
                if (! $exercise->uuid) {
                    $exercise->forceFill(['uuid' => (string) Str::uuid()])->saveQuietly();
                }

                $record = $this->portableRecord($exercise);

                if ($exercise->media_path && Storage::disk('local')->exists($exercise->media_path)) {
                    $extension = strtolower(pathinfo($exercise->media_path, PATHINFO_EXTENSION));
                    $mediaPath = 'media/'.$exercise->uuid.'.'.$extension;

                    $archive->addFromString($mediaPath, Storage::disk('local')->get($exercise->media_path));
                    $record['media'] = [
                        'path' => $mediaPath,
                        'mime' => $exercise->media_type,
                    ];
                }

                return $record;
            })->all();

            $manifest = json_encode([
                'format' => 'u22-basketball-exercise-library',
                'version' => 1,
                'exported_at' => now()->toIso8601String(),
                'exercises' => $records,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

            $archive->addFromString('manifest.json', $manifest);
        } finally {
            $archive->close();
        }

        return $path;
    }

    /** @return array<string, mixed> */
    private function portableRecord(ExerciseLibraryItem $exercise): array
    {
        return [
            'uuid' => $exercise->uuid,
            'category' => $exercise->category,
            'scope' => $exercise->scope->value,
            'name' => $exercise->name,
            'description' => $exercise->description,
            'objective' => $exercise->objective,
            'organization' => $exercise->organization,
            'execution' => $exercise->execution,
            'default_duration_minutes' => $exercise->default_duration_minutes,
            'min_players' => $exercise->min_players,
            'max_players' => $exercise->max_players,
            'baskets_required' => $exercise->baskets_required,
            'intensity' => $exercise->intensity,
            'materials' => $exercise->materials,
            'coaching_points' => $exercise->coaching_points,
            'constraints' => $exercise->constraints,
            'regressions' => $exercise->regressions,
            'progressions' => $exercise->progressions,
            'tags' => $exercise->tags,
            'coach_notes' => $exercise->coach_notes,
            'video_url' => $exercise->video_url,
            'external_url' => $exercise->external_url,
            'default_coach' => $exercise->default_coach->value,
            'coaching_cues' => $exercise->coaching_cues ?? '',
            'common_mistakes' => $exercise->common_mistakes,
            'sort_order' => $exercise->sort_order,
            'media' => null,
        ];
    }
}
