<?php

use App\Enums\TrainingBlockRunStatus;
use App\Enums\TrainingRunStatus;
use App\Livewire\Coach\Trainings\Review;
use App\Models\TrainingBlockRun;
use App\Models\TrainingRun;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

function reviewedTrainingRun(): TrainingRun
{
    $training = TrainingSession::factory()->create([
        'title' => 'Transitie onder druk',
        'coach_notes' => 'Alleen voor de eigen staf.',
    ]);
    $block = $training->blocks()->create([
        'block_type' => 'text',
        'position' => 1,
        'title' => 'Transition drill',
        'assigned_coach' => 'Raki',
        'planned_duration_minutes' => 15,
    ]);
    $run = TrainingRun::factory()->for($training, 'trainingSession')->create([
        'status' => TrainingRunStatus::Completed,
        'started_at' => now()->subHour(),
        'ended_at' => now(),
        'general_notes' => 'Goede energie in de groep.',
        'what_worked' => 'De communicatie in transitie.',
        'what_to_change' => 'Kortere wachtrijen maken.',
        'next_action' => 'Volgende week opnieuw filmen.',
    ]);
    TrainingBlockRun::factory()->for($run, 'trainingRun')->for($block, 'trainingBlock')->create([
        'status' => TrainingBlockRunStatus::Completed,
        'actual_duration_seconds' => 900,
        'notes' => 'Alleen voor de eigen staf.',
    ]);

    return $run;
}

test('a signed evaluation share link shows the evaluation without private notes', function () {
    $run = reviewedTrainingRun();
    $shareUrl = URL::temporarySignedRoute('training-runs.evaluation-share', now()->addDays(30), ['trainingRun' => $run]);

    $this->get($shareUrl)
        ->assertSuccessful()
        ->assertSee('Transitie onder druk')
        ->assertSee('Goede energie in de groep.')
        ->assertSee('De communicatie in transitie.')
        ->assertDontSee('Alleen voor de eigen staf.')
        ->assertSee('property="og:title"', false)
        ->assertSee('u22-training-share-preview.png', false);
});

test('an unsigned evaluation share link is forbidden', function () {
    $run = reviewedTrainingRun();

    $this->get(route('training-runs.evaluation-share', $run))->assertForbidden();
});

test('a trainer can leave feedback through a signed link', function () {
    $run = reviewedTrainingRun();
    $feedbackUrl = URL::temporarySignedRoute('training-runs.evaluation-feedback', now()->addDays(30), ['trainingRun' => $run]);

    $this->post($feedbackUrl, [
        'author_name' => 'Assistent coach',
        'feedback' => 'Sterke opbouw. Voeg de volgende keer een extra read toe.',
    ])->assertRedirect();

    $this->assertDatabaseHas('training_run_feedback', [
        'training_run_id' => $run->id,
        'author_name' => 'Assistent coach',
        'feedback' => 'Sterke opbouw. Voeg de volgende keer een extra read toe.',
    ]);
});

test('the feedback form rejects automated submissions', function () {
    $run = reviewedTrainingRun();
    $feedbackUrl = URL::temporarySignedRoute('training-runs.evaluation-feedback', now()->addDays(30), ['trainingRun' => $run]);

    $this->from(URL::temporarySignedRoute('training-runs.evaluation-share', now()->addDays(30), ['trainingRun' => $run]))
        ->post($feedbackUrl, [
            'author_name' => 'Automated sender',
            'feedback' => 'Dit bericht mag niet worden opgeslagen.',
            'website' => 'https://spam.example',
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('website');

    $this->assertDatabaseMissing('training_run_feedback', ['author_name' => 'Automated sender']);
});

test('a coach sees the WhatsApp share button and received feedback', function () {
    $coach = User::factory()->coach()->create();
    $run = reviewedTrainingRun();
    $run->feedback()->create(['author_name' => 'Assistent coach', 'feedback' => 'Lekker tempo en duidelijke uitleg.']);

    Livewire::actingAs($coach)
        ->test(Review::class, ['training' => $run->trainingSession])
        ->assertSee('Evaluatie delen via WhatsApp')
        ->assertSee('wa.me/?text=', false)
        ->assertSee('Assistent coach')
        ->assertSee('Lekker tempo en duidelijke uitleg.');
});
