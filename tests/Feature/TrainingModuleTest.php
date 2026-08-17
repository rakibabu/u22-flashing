<?php

use App\Actions\Training\DuplicateTrainingSession;
use App\Actions\Training\ReorderTrainingBlocks;
use App\Actions\Training\StartTrainingRun;
use App\Enums\TrainingBlockRunStatus;
use App\Enums\TrainingStatus;
use App\Livewire\Coach\Exercises\Index as ExerciseIndex;
use App\Livewire\Coach\Trainings\Builder;
use App\Livewire\Coach\Trainings\Run as TrainingRunComponent;
use App\Models\ExerciseLibraryItem;
use App\Models\Player;
use App\Models\TrainingSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('updating an exercise refreshes its content in future training blocks', function () {
    $coach = User::factory()->coach()->create();
    $exercise = ExerciseLibraryItem::factory()->create(['name' => 'Closeout', 'coaching_points' => ['Onder controle']]);
    $training = TrainingSession::factory()->create(['created_by' => $coach->id]);

    Livewire::actingAs($coach)->test(Builder::class, ['training' => $training])
        ->call('addExercise', $exercise->id);

    $block = $training->blocks()->firstOrFail();
    $block->update(['title' => 'Eigen bloktitel', 'assigned_coach' => 'Tim', 'planned_duration_minutes' => 15]);

    Livewire::actingAs($coach)->test(ExerciseIndex::class)
        ->call('edit', $exercise->id)
        ->set('name', 'Aangepaste closeout')
        ->set('execution', 'Sluit uit met gecontroleerde stappen.')
        ->call('save')
        ->assertHasNoErrors();

    expect($block->fresh()->exercise_snapshot['name'])->toBe('Aangepaste closeout')
        ->and($block->fresh()->exercise_snapshot['execution'])->toBe('Sluit uit met gecontroleerde stappen.')
        ->and($block->fresh()->title)->toBe('Eigen bloktitel')
        ->and($block->fresh()->assigned_coach->value)->toBe('Tim')
        ->and($block->fresh()->planned_duration_minutes)->toBe(15);
});

test('updating an exercise does not change an in-progress training', function () {
    $coach = User::factory()->coach()->create();
    $exercise = ExerciseLibraryItem::factory()->create(['name' => 'Closeout']);
    $training = TrainingSession::factory()->create(['created_by' => $coach->id, 'status' => TrainingStatus::InProgress]);
    $block = $training->blocks()->create(['exercise_library_item_id' => $exercise->id, 'block_type' => 'exercise', 'position' => 1, 'title' => $exercise->name, 'assigned_coach' => 'Raki', 'planned_duration_minutes' => 10, 'exercise_snapshot' => ['name' => $exercise->name]]);

    Livewire::actingAs($coach)->test(ExerciseIndex::class)
        ->call('edit', $exercise->id)
        ->set('name', 'Aangepaste closeout')
        ->call('save')
        ->assertHasNoErrors();

    expect($block->fresh()->exercise_snapshot['name'])->toBe('Closeout');
});

test('coach saves a training date in Amsterdam time', function () {
    $coach = User::factory()->coach()->create();
    $training = TrainingSession::factory()->create(['created_by' => $coach->id, 'scheduled_at' => null]);

    Livewire::actingAs($coach)
        ->test(Builder::class, ['training' => $training])
        ->set('scheduledAt', '2026-09-14T19:30')
        ->call('save')
        ->assertHasNoErrors();

    expect($training->fresh()->scheduled_at?->utc()->format('Y-m-d H:i'))->toBe('2026-09-14 17:30')
        ->and($training->fresh()->scheduled_at?->timezone('Europe/Amsterdam')->format('Y-m-d H:i'))->toBe('2026-09-14 19:30');
});

test('coach can open the empty new exercise form', function () {
    $coach = User::factory()->coach()->create();

    Livewire::actingAs($coach)
        ->test(ExerciseIndex::class)
        ->call('edit')
        ->assertSet('showForm', true)
        ->assertSee('Korte samenvatting');
});

test('new exercise appears in the library immediately after saving', function () {
    $coach = User::factory()->coach()->create();
    ExerciseLibraryItem::factory()->create(['name' => 'Bestaande passing', 'category' => 'passing']);

    Livewire::actingAs($coach)
        ->test(ExerciseIndex::class)
        ->set('search', 'bestaande oefening')
        ->set('name', 'Nieuwe closeout')
        ->set('formCategory', 'defence')
        ->set('description', 'Verdedig de closeout.')
        ->set('execution', 'Sluit gecontroleerd uit.')
        ->set('coachingCues', 'Hand omhoog.')
        ->call('save')
        ->assertSet('search', '')
        ->assertSet('category', '')
        ->assertSee('Nieuwe closeout')
        ->assertSee('Bestaande passing');
});

test('training duplicate preserves blocks but not execution data', function () {
    $coach = User::factory()->coach()->create();
    $training = TrainingSession::factory()->create(['created_by' => $coach->id]);
    $training->blocks()->create(['block_type' => 'text', 'position' => 1, 'title' => 'Briefing', 'planned_duration_minutes' => 10, 'exercise_snapshot' => ['name' => 'Historisch']]);
    $run = app(StartTrainingRun::class)->handle($training, $coach);

    $copy = app(DuplicateTrainingSession::class)->handle($training, $coach);

    expect($copy->status)->toBe(TrainingStatus::Draft)
        ->and($copy->blocks)->toHaveCount(1)
        ->and($copy->blocks->first()->exercise_snapshot['name'])->toBe('Historisch')
        ->and($copy->runs)->toHaveCount(0)
        ->and($run)->not->toBeNull();
});

test('blocks reorder transaction keeps intended order', function () {
    $training = TrainingSession::factory()->create();
    $first = $training->blocks()->create(['block_type' => 'text', 'position' => 1, 'title' => 'Een', 'planned_duration_minutes' => 5]);
    $second = $training->blocks()->create(['block_type' => 'text', 'position' => 2, 'title' => 'Twee', 'planned_duration_minutes' => 5]);

    app(ReorderTrainingBlocks::class)->handle($training, [$second->id, $first->id]);

    expect($training->blocks()->pluck('id')->all())->toBe([$second->id, $first->id]);
});

test('training run resumes and keeps actual time separate from planned time', function () {
    $coach = User::factory()->coach()->create();
    $training = TrainingSession::factory()->create(['created_by' => $coach->id]);
    $block = $training->blocks()->create(['block_type' => 'text', 'position' => 1, 'title' => 'Briefing', 'planned_duration_minutes' => 5]);

    $run = app(StartTrainingRun::class)->handle($training, $coach);
    $sameRun = app(StartTrainingRun::class)->handle($training, $coach);
    $blockRun = $run->blockRuns()->firstOrFail();
    $blockRun->update(['status' => TrainingBlockRunStatus::Skipped, 'actual_duration_seconds' => 73, 'added_duration_seconds' => 120]);

    expect($sameRun->id)->toBe($run->id)
        ->and($blockRun->fresh()->actual_duration_seconds)->toBe(73)
        ->and($block->fresh()->planned_duration_minutes)->toBe(5);
});

test('training timer is reinitialized for each training block', function () {
    $this->travelTo(Carbon::parse('2026-08-15 17:00:00'));
    $coach = User::factory()->coach()->create();
    $training = TrainingSession::factory()->create(['created_by' => $coach->id]);
    $first = $training->blocks()->create(['block_type' => 'text', 'position' => 1, 'title' => 'Warming-up', 'planned_duration_minutes' => 30]);
    $second = $training->blocks()->create(['block_type' => 'text', 'position' => 2, 'title' => 'Shooting', 'planned_duration_minutes' => 10]);

    $component = Livewire::actingAs($coach)->test(TrainingRunComponent::class, ['training' => $training])
        ->assertDontSee('training-run-block-', false)
        ->assertSee('training-block-changed.window', false)
        ->assertSee('planned: 1800', false);

    $this->travel(2)->seconds();

    $component->call('next')
        ->assertDispatched('training-block-changed')
        ->assertSee('Shooting')
        ->assertSee('planned: 600', false);
});

test('training run renders valid offline training data for Alpine', function () {
    $coach = User::factory()->coach()->create();
    $training = TrainingSession::factory()->create(['created_by' => $coach->id]);
    $training->blocks()->create(['block_type' => 'text', 'position' => 1, 'title' => 'Briefing', 'planned_duration_minutes' => 5]);

    Livewire::actingAs($coach)
        ->test(TrainingRunComponent::class, ['training' => $training])
        ->assertSee('Meer acties')
        ->assertSee('additionalControlsOpen', false)
        ->assertSee('trainingOffline.saveTraining(offlineTraining)', false)
        ->assertSee('trainingId:', false)
        ->assertSee('offlineSaving', false)
        ->assertSee('Offline opslaan lukt niet op dit toestel.', false)
        ->assertDontSee('@js(', false);
});

test('player cannot access coach training pages or private exercise media', function () {
    Storage::fake('local');
    $playerUser = User::factory()->player()->create();
    $exercise = ExerciseLibraryItem::factory()->create(['media_path' => 'exercise-media/private.pdf', 'media_type' => 'application/pdf']);
    Storage::disk('local')->put($exercise->media_path, '%PDF-demo');

    $this->actingAs($playerUser)->get(route('coach.trainings.index'))->assertForbidden();
    $this->actingAs($playerUser)->get(route('coach.exercises.media', $exercise))->assertForbidden();
});

test('attendance is unique per player and run', function () {
    $coach = User::factory()->coach()->create();
    $player = Player::factory()->create();
    $training = TrainingSession::factory()->create(['created_by' => $coach->id]);
    $training->blocks()->create(['block_type' => 'text', 'position' => 1, 'title' => 'Start', 'planned_duration_minutes' => 5]);
    $run = app(StartTrainingRun::class)->handle($training, $coach);

    $run->attendances()->updateOrCreate(['player_id' => $player->id], ['status' => 'present']);
    $run->attendances()->updateOrCreate(['player_id' => $player->id], ['status' => 'late']);

    expect($run->attendances()->count())->toBe(1)
        ->and($run->attendances()->first()->status->value)->toBe('late');
});

test('offline event sync is idempotent and only accepts blocks of the run training', function () {
    $coach = User::factory()->coach()->create();
    $training = TrainingSession::factory()->create(['created_by' => $coach->id]);
    $block = $training->blocks()->create(['block_type' => 'text', 'position' => 1, 'title' => 'Start', 'planned_duration_minutes' => 5]);
    $run = app(StartTrainingRun::class)->handle($training, $coach);
    $event = ['uuid' => '75e8d707-bbdb-4853-9394-3b6fcfdba985', 'sequence' => 1, 'type' => 'note', 'block_id' => $block->id, 'payload' => ['notes' => 'Lokaal opgeslagen']];

    $this->actingAs($coach)->postJson(route('coach.training-runs.offline-events', $run), ['events' => [$event]])->assertSuccessful();
    $this->actingAs($coach)->postJson(route('coach.training-runs.offline-events', $run), ['events' => [$event]])->assertSuccessful();

    expect($run->fresh()->blockRuns()->first()->notes)->toBe('Lokaal opgeslagen')
        ->and(DB::table('training_offline_events')->count())->toBe(1);
});
