<?php

namespace App\Actions\ExerciseLibrary;

use App\Enums\ExerciseScope;
use App\Enums\TrainingCoach;
use App\Models\ExerciseLibraryItem;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use JsonException;
use ZipArchive;

class ImportExerciseLibrary
{
    /** @var array<string, string> */
    private const MEDIA_MIMES = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'pdf' => 'application/pdf',
    ];

    public function handle(UploadedFile $archiveFile, User $importedBy): int
    {
        $archive = new ZipArchive;

        if ($archive->open($archiveFile->getRealPath()) !== true) {
            throw ValidationException::withMessages(['archive' => 'Dit ZIP-bestand kan niet worden geopend.']);
        }

        try {
            $manifest = $this->manifest($archive);
            $records = $this->validatedRecords($manifest, $archive);

            DB::transaction(function () use ($archive, $records, $importedBy): void {
                foreach ($records as $record) {
                    $this->storeRecord($archive, $record, $importedBy);
                }
            });
        } finally {
            $archive->close();
        }

        return count($records);
    }

    /** @return array<string, mixed> */
    private function manifest(ZipArchive $archive): array
    {
        if ($archive->numFiles > 501) {
            throw ValidationException::withMessages(['archive' => 'Dit ZIP-bestand bevat te veel bestanden.']);
        }

        $uncompressedSize = 0;

        for ($index = 0; $index < $archive->numFiles; $index++) {
            $stat = $archive->statIndex($index);
            $uncompressedSize += $stat['size'] ?? 0;
        }

        if ($uncompressedSize > 100 * 1024 * 1024) {
            throw ValidationException::withMessages(['archive' => 'De uitgepakte import is te groot.']);
        }

        $manifest = $archive->getFromName('manifest.json');

        if (! is_string($manifest) || strlen($manifest) > 2_000_000) {
            throw ValidationException::withMessages(['archive' => 'Dit ZIP-bestand bevat geen geldig manifest.']);
        }

        try {
            $data = json_decode($manifest, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw ValidationException::withMessages(['archive' => 'Het oefeningsmanifest is geen geldige JSON.']);
        }

        if (! is_array($data) || ($data['format'] ?? null) !== 'u22-basketball-exercise-library' || ($data['version'] ?? null) !== 1 || ! isset($data['exercises']) || ! is_array($data['exercises']) || ! array_is_list($data['exercises'])) {
            throw ValidationException::withMessages(['archive' => 'Dit is geen ondersteund oefeningsarchief.']);
        }

        return $data;
    }

    /** @param array<string, mixed> $manifest
     * @return array<int, array<string, mixed>>
     */
    private function validatedRecords(array $manifest, ZipArchive $archive): array
    {
        if ($manifest['exercises'] === []) {
            throw ValidationException::withMessages(['archive' => 'Dit archief bevat geen oefeningen. Exporteer de actieve oefeningen opnieuw vanuit je lokale omgeving.']);
        }

        if (count($manifest['exercises']) > 500) {
            throw ValidationException::withMessages(['archive' => 'Een import mag maximaal 500 oefeningen bevatten.']);
        }

        return collect($manifest['exercises'])->map(function (mixed $record, int $index) use ($archive): array {
            if (! is_array($record)) {
                throw ValidationException::withMessages(['archive' => 'Oefening '.($index + 1).' is ongeldig.']);
            }

            $validated = Validator::make($record, $this->recordRules())->validate();
            $this->validateMedia($archive, $validated, $index);

            return $validated;
        })->all();
    }

    /** @return array<string, array<int, string>> */
    private function recordRules(): array
    {
        return [
            'uuid' => ['required', 'uuid'],
            'category' => ['required', 'string', 'max:255'],
            'scope' => ['required', 'in:'.implode(',', array_column(ExerciseScope::cases(), 'value'))],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'objective' => ['nullable', 'string'],
            'organization' => ['nullable', 'string'],
            'execution' => ['required', 'string'],
            'default_duration_minutes' => ['nullable', 'integer', 'min:1', 'max:360'],
            'min_players' => ['nullable', 'integer', 'min:1', 'max:99'],
            'max_players' => ['nullable', 'integer', 'min:1', 'max:99', 'gte:min_players'],
            'baskets_required' => ['nullable', 'integer', 'min:0', 'max:20'],
            'intensity' => ['nullable', 'string', 'max:50'],
            'materials' => ['nullable', 'array'],
            'materials.*' => ['string', 'max:255'],
            'coaching_points' => ['nullable', 'array'],
            'coaching_points.*' => ['string', 'max:255'],
            'constraints' => ['nullable', 'array'],
            'constraints.*' => ['string', 'max:255'],
            'regressions' => ['nullable', 'array'],
            'regressions.*' => ['string', 'max:255'],
            'progressions' => ['nullable', 'array'],
            'progressions.*' => ['string', 'max:255'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:255'],
            'coach_notes' => ['nullable', 'string'],
            'video_url' => ['nullable', 'url:https', 'max:2048'],
            'external_url' => ['nullable', 'url:https', 'max:2048'],
            'default_coach' => ['required', 'in:'.implode(',', array_column(TrainingCoach::cases(), 'value'))],
            'coaching_cues' => ['nullable', 'string'],
            'common_mistakes' => ['nullable', 'string'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
            'media' => ['nullable', 'array'],
            'media.path' => ['required_with:media', 'string'],
            'media.mime' => ['required_with:media', 'string', 'in:'.implode(',', self::MEDIA_MIMES)],
        ];
    }

    /** @param array<string, mixed> $record */
    private function validateMedia(ZipArchive $archive, array $record, int $index): void
    {
        if (! isset($record['media'])) {
            return;
        }

        $path = $record['media']['path'];
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (! preg_match('/^media\/[0-9a-f-]+\.(jpg|jpeg|png|webp|pdf)$/i', $path) || ! isset(self::MEDIA_MIMES[$extension]) || self::MEDIA_MIMES[$extension] !== $record['media']['mime']) {
            throw ValidationException::withMessages(['archive' => 'De bijlage van oefening '.($index + 1).' is ongeldig.']);
        }

        $stat = $archive->statName($path);

        if ($stat === false || $stat['size'] > 10 * 1024 * 1024) {
            throw ValidationException::withMessages(['archive' => 'De bijlage van oefening '.($index + 1).' is te groot of ontbreekt.']);
        }
    }

    /** @param array<string, mixed> $record */
    private function storeRecord(ZipArchive $archive, array $record, User $importedBy): void
    {
        $mediaPath = null;
        $mediaType = null;

        if (isset($record['media'])) {
            $contents = $archive->getFromName($record['media']['path']);
            $extension = strtolower(pathinfo($record['media']['path'], PATHINFO_EXTENSION));
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = $finfo !== false && is_string($contents) ? finfo_buffer($finfo, $contents) : false;

            if (! is_string($contents) || $mime !== self::MEDIA_MIMES[$extension]) {
                throw ValidationException::withMessages(['archive' => 'Een bijlage heeft een onjuist bestandstype.']);
            }

            $mediaPath = 'exercise-media/'.$record['uuid'].'.'.$extension;
            $mediaType = self::MEDIA_MIMES[$extension];
            Storage::disk('local')->put($mediaPath, $contents);
        }

        $attributes = collect($record)->except(['uuid', 'media'])->all();
        $attributes['coaching_cues'] ??= '';
        $attributes['media_path'] = $mediaPath;
        $attributes['media_type'] = $mediaType;

        $exercise = ExerciseLibraryItem::query()->where('uuid', $record['uuid'])->first() ?? new ExerciseLibraryItem;

        if (! $exercise->exists) {
            $exercise->forceFill(['uuid' => $record['uuid']]);
        }

        $previousMediaPath = $exercise->media_path;
        $exercise->fill($attributes);
        $exercise->created_by ??= $importedBy->id;
        $exercise->save();

        if ($previousMediaPath && $previousMediaPath !== $mediaPath) {
            Storage::disk('local')->delete($previousMediaPath);
        }
    }
}
