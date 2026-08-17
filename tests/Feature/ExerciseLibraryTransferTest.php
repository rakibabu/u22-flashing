<?php

use App\Models\ExerciseLibraryItem;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('coach can export active exercises and their media', function () {
    Storage::fake('local');
    $coach = User::factory()->coach()->create();
    $exercise = ExerciseLibraryItem::factory()->create([
        'name' => 'Closeout met media',
        'materials' => ['2 ballen'],
        'media_path' => 'exercise-media/closeout.pdf',
        'media_type' => 'application/pdf',
    ]);
    ExerciseLibraryItem::factory()->create(['archived_at' => now(), 'name' => 'Gearchiveerd']);
    Storage::disk('local')->put($exercise->media_path, '%PDF-1.4 test');

    $response = $this->actingAs($coach)->get(route('coach.exercises.export'));

    $response->assertDownload('oefeningen-'.now()->format('Y-m-d').'.zip');

    $archivePath = tempnam(sys_get_temp_dir(), 'exercise-export-');
    file_put_contents($archivePath, $response->streamedContent());
    $archive = new ZipArchive;
    $archive->open($archivePath);
    $manifest = json_decode($archive->getFromName('manifest.json'), true);

    expect($manifest['format'])->toBe('u22-basketball-exercise-library')
        ->and($manifest['exercises'])->toHaveCount(1)
        ->and($manifest['exercises'][0]['uuid'])->toBe($exercise->uuid)
        ->and($manifest['exercises'][0]['media']['mime'])->toBe('application/pdf')
        ->and($archive->getFromName($manifest['exercises'][0]['media']['path']))->toBe('%PDF-1.4 test');

    $archive->close();
    unlink($archivePath);
});

test('coach can import exercises and their media without duplicates', function () {
    Storage::fake('local');
    $coach = User::factory()->coach()->create();
    $archive = exerciseArchive([[
        'uuid' => '1e5f312c-18c1-4f4b-b345-95d47f02df8d',
        'category' => 'passing',
        'scope' => 'team',
        'name' => 'Drive and kick',
        'description' => 'Creëer een extra pass.',
        'objective' => 'Beslissen',
        'organization' => 'Halve baan',
        'execution' => 'Drive, kick en extra pass.',
        'default_duration_minutes' => 12,
        'min_players' => 4,
        'max_players' => 8,
        'baskets_required' => 1,
        'intensity' => 'hoog',
        'materials' => ['2 ballen'],
        'coaching_points' => ['Kijk vooruit'],
        'constraints' => ['Maximaal twee dribbels'],
        'regressions' => [],
        'progressions' => ['Extra verdediger'],
        'tags' => ['drive'],
        'coach_notes' => 'Werk aan timing.',
        'video_url' => 'https://example.com/video',
        'external_url' => null,
        'default_coach' => 'Tim',
        'coaching_cues' => 'Kijk naar de weak side.',
        'common_mistakes' => null,
        'sort_order' => 4,
        'media' => ['path' => 'media/1e5f312c-18c1-4f4b-b345-95d47f02df8d.pdf', 'mime' => 'application/pdf'],
    ]], ['media/1e5f312c-18c1-4f4b-b345-95d47f02df8d.pdf' => '%PDF-1.4 test']);

    $this->actingAs($coach)->post(route('coach.exercises.import'), ['archive' => UploadedFile::fake()->createWithContent('oefeningen.zip', file_get_contents($archive))])
        ->assertRedirect(route('coach.exercises.index'))
        ->assertSessionHas('exercise-imported');

    $this->get(route('coach.exercises.index'))
        ->assertSee('Import voltooid')
        ->assertSee('1 oefening geïmporteerd.');

    $this->actingAs($coach)->post(route('coach.exercises.import'), ['archive' => UploadedFile::fake()->createWithContent('oefeningen.zip', file_get_contents($archive))]);

    $exercise = ExerciseLibraryItem::query()->sole();
    expect($exercise->name)->toBe('Drive and kick');
    expect($exercise->scope->value)->toBe('team');
    expect($exercise->coaching_points)->toBe(['Kijk vooruit']);
    expect($exercise->creator->id)->toBe($coach->id);
    expect(ExerciseLibraryItem::query()->count())->toBe(1);
    Storage::disk('local')->assertExists('exercise-media/1e5f312c-18c1-4f4b-b345-95d47f02df8d.pdf');

    unlink($archive);
});

test('invalid exercise import does not create partial records', function () {
    $coach = User::factory()->coach()->create();
    $archive = exerciseArchive([['name' => 'Zonder verplichte velden']]);

    $this->actingAs($coach)->from(route('coach.exercises.index'))
        ->post(route('coach.exercises.import'), ['archive' => UploadedFile::fake()->createWithContent('oefeningen.zip', file_get_contents($archive))])
        ->assertRedirect(route('coach.exercises.index'))
        ->assertSessionHas('exercise-import-error');

    expect(ExerciseLibraryItem::query()->count())->toBe(0);

    unlink($archive);
});

test('empty exercise import shows a clear error', function () {
    $coach = User::factory()->coach()->create();
    $archive = exerciseArchive([]);

    $this->actingAs($coach)->from(route('coach.exercises.index'))
        ->post(route('coach.exercises.import'), ['archive' => UploadedFile::fake()->createWithContent('oefeningen.zip', file_get_contents($archive))])
        ->assertRedirect(route('coach.exercises.index'))
        ->assertSessionHas('exercise-import-error');

    $this->get(route('coach.exercises.index'))
        ->assertSee('Importeren mislukt')
        ->assertSee('Dit archief bevat geen oefeningen.');

    expect(ExerciseLibraryItem::query()->count())->toBe(0);

    unlink($archive);
});

test('invalid upload shows a clear error', function () {
    $coach = User::factory()->coach()->create();

    $this->actingAs($coach)->from(route('coach.exercises.index'))
        ->post(route('coach.exercises.import'))
        ->assertRedirect(route('coach.exercises.index'))
        ->assertSessionHas('exercise-import-error');

    $this->get(route('coach.exercises.index'))
        ->assertSee('Importeren mislukt')
        ->assertSee('Kies een ZIP-bestand om te importeren.');
});

test('player cannot import or export exercises', function () {
    $player = User::factory()->player()->create();

    $this->actingAs($player)->get(route('coach.exercises.export'))->assertForbidden();
    $this->actingAs($player)->post(route('coach.exercises.import'), ['archive' => UploadedFile::fake()->create('oefeningen.zip')])->assertForbidden();
});

/** @param array<int, array<string, mixed>> $exercises
 * @param  array<string, string>  $files
 */
function exerciseArchive(array $exercises, array $files = []): string
{
    $path = tempnam(sys_get_temp_dir(), 'exercise-import-');
    $archive = new ZipArchive;
    $archive->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $archive->addFromString('manifest.json', json_encode([
        'format' => 'u22-basketball-exercise-library',
        'version' => 1,
        'exercises' => $exercises,
    ], JSON_THROW_ON_ERROR));

    foreach ($files as $name => $contents) {
        $archive->addFromString($name, $contents);
    }

    $archive->close();

    return $path;
}
