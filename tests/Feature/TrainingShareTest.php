<?php

use App\Livewire\Coach\Trainings\Builder;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

test('a signed training share link shows the training content and U22 invitation', function () {
    $training = TrainingSession::factory()->create(['title' => 'Verdedigen onder druk', 'goals' => 'Communicatie verbeteren.']);
    $training->blocks()->create([
        'block_type' => 'text',
        'position' => 1,
        'title' => 'Close-out circuit',
        'assigned_coach' => 'Raki',
        'planned_duration_minutes' => 12,
        'coach_notes' => 'Blijf laag in je stance.',
    ]);

    $shareUrl = URL::temporarySignedRoute('trainings.share', now()->addDays(30), ['training' => $training]);

    $this->get($shareUrl)
        ->assertSuccessful()
        ->assertSee('Verdedigen onder druk')
        ->assertSee('Close-out circuit')
        ->assertDontSee('Blijf laag in je stance.')
        ->assertSee('Werk met U22 Monitoring.')
        ->assertSee('property="og:title"', false)
        ->assertSee('property="og:image"', false)
        ->assertSee('u22-training-share-preview.png', false);
});

test('an unsigned training share link is forbidden', function () {
    $training = TrainingSession::factory()->create();

    $this->get(route('trainings.share', $training))->assertForbidden();
});

test('an expired training share link is forbidden', function () {
    $training = TrainingSession::factory()->create();
    $shareUrl = URL::temporarySignedRoute('trainings.share', now()->subMinute(), ['training' => $training]);

    $this->get($shareUrl)->assertForbidden();
});

test('a coach can share a training through WhatsApp', function () {
    $coach = User::factory()->coach()->create();
    $training = TrainingSession::factory()->create(['created_by' => $coach->id]);

    Livewire::actingAs($coach)
        ->test(Builder::class, ['training' => $training])
        ->assertSee('Delen via WhatsApp')
        ->assertSee('wa.me/?text=', false);
});
