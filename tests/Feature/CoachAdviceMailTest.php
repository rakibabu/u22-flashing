<?php

use App\Livewire\Coach\Advice\Index as CoachAdviceIndex;
use App\Livewire\Coach\Players\Show;
use App\Mail\CoachAdviceWritten;
use App\Models\CoachNote;
use App\Models\Player;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

function coachAdviceMailPlayer(?string $email = 'speler@example.test'): Player
{
    $user = User::factory()->player()->create(['email' => $email]);

    return Player::factory()->create([
        'user_id' => $user->id,
        'program_type' => Player::Maintenance,
    ]);
}

test('zichtbaar advies mailt de speler', function () {
    Mail::fake();

    $coach = User::factory()->coach()->create();
    $player = coachAdviceMailPlayer();

    Livewire::actingAs($coach)
        ->test(Show::class, ['player' => $player])
        ->set('adviceBody', 'Plan deze week twee rustige krachtmomenten.')
        ->set('visibleToPlayer', true)
        ->call('saveAdvice');

    $coachNote = CoachNote::query()->whereBelongsTo($player)->firstOrFail();

    expect($coachNote->sent_at)->not->toBeNull();

    Mail::assertQueued(CoachAdviceWritten::class, function (CoachAdviceWritten $mail) use ($coachNote, $player): bool {
        return $mail->hasTo($player->user->email)
            && $mail->coachNote->is($coachNote);
    });
});

test('verborgen advies mailt de speler niet', function () {
    Mail::fake();

    $coach = User::factory()->coach()->create();
    $player = coachAdviceMailPlayer();

    Livewire::actingAs($coach)
        ->test(Show::class, ['player' => $player])
        ->set('adviceBody', 'Bewaar dit advies nog even intern.')
        ->set('visibleToPlayer', false)
        ->call('saveAdvice');

    $coachNote = CoachNote::query()->whereBelongsTo($player)->firstOrFail();

    expect($coachNote->sent_at)->toBeNull();

    Mail::assertNothingQueued();
});

test('zichtbaar maken vanuit de advieslijst mailt eenmalig', function () {
    Mail::fake();

    $coach = User::factory()->coach()->create();
    $player = coachAdviceMailPlayer();
    $coachNote = CoachNote::query()->create([
        'player_id' => $player->id,
        'coach_user_id' => $coach->id,
        'week_start_date' => now()->startOfWeek()->toDateString(),
        'type' => 'advice',
        'title' => 'Coachadvies',
        'body' => 'Maak dit advies zichtbaar voor de speler.',
        'visible_to_player' => false,
    ]);

    Livewire::actingAs($coach)
        ->test(CoachAdviceIndex::class)
        ->call('toggleVisible', $coachNote->id);

    expect($coachNote->refresh()->visible_to_player)->toBeTrue()
        ->and($coachNote->sent_at)->not->toBeNull();

    Mail::assertQueuedCount(1);

    Livewire::actingAs($coach)
        ->test(CoachAdviceIndex::class)
        ->call('toggleVisible', $coachNote->id)
        ->call('toggleVisible', $coachNote->id);

    expect($coachNote->refresh()->visible_to_player)->toBeTrue()
        ->and($coachNote->sent_at)->not->toBeNull();

    Mail::assertQueuedCount(1);
});

test('speler zonder e-mailadres krijgt geen adviesmail', function () {
    Mail::fake();

    $coach = User::factory()->coach()->create();
    $player = coachAdviceMailPlayer(email: null);

    Livewire::actingAs($coach)
        ->test(Show::class, ['player' => $player])
        ->set('adviceBody', 'Dit advies is zichtbaar maar er is geen e-mailadres.')
        ->set('visibleToPlayer', true)
        ->call('saveAdvice');

    $coachNote = CoachNote::query()->whereBelongsTo($player)->firstOrFail();

    expect($coachNote->sent_at)->toBeNull();

    Mail::assertNothingQueued();
});
