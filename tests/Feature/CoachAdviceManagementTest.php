<?php

use App\Livewire\Coach\Advice\Index as CoachAdviceIndex;
use App\Models\CoachNote;
use App\Models\Player;
use App\Models\User;
use Livewire\Livewire;

function coachAdviceManagementPlayer(): Player
{
    $user = User::factory()->player()->create();

    return Player::factory()->create([
        'user_id' => $user->id,
        'program_type' => Player::Maintenance,
    ]);
}

test('coach kan advies verwijderen', function () {
    $coach = User::factory()->coach()->create();
    $player = coachAdviceManagementPlayer();
    $coachNote = CoachNote::query()->create([
        'player_id' => $player->id,
        'coach_user_id' => $coach->id,
        'week_start_date' => now()->startOfWeek()->toDateString(),
        'type' => 'advice',
        'title' => 'Coachadvies',
        'body' => 'Dit advies mag weg.',
        'visible_to_player' => true,
        'sent_at' => now(),
    ]);

    Livewire::actingAs($coach)
        ->test(CoachAdviceIndex::class)
        ->assertSee('Dit advies mag weg.')
        ->assertSee('Verwijder')
        ->call('delete', $coachNote->id)
        ->assertDispatched('advice-deleted');

    expect(CoachNote::query()->whereKey($coachNote->id)->exists())->toBeFalse()
        ->and(Player::query()->whereKey($player->id)->exists())->toBeTrue();
});

test('speler kan advies niet verwijderen', function () {
    $coach = User::factory()->coach()->create();
    $player = coachAdviceManagementPlayer();
    $coachNote = CoachNote::query()->create([
        'player_id' => $player->id,
        'coach_user_id' => $coach->id,
        'week_start_date' => now()->startOfWeek()->toDateString(),
        'type' => 'advice',
        'title' => 'Coachadvies',
        'body' => 'Dit advies blijft staan.',
        'visible_to_player' => true,
        'sent_at' => now(),
    ]);

    Livewire::actingAs($player->user)
        ->test(CoachAdviceIndex::class)
        ->call('delete', $coachNote->id)
        ->assertForbidden();

    expect(CoachNote::query()->whereKey($coachNote->id)->exists())->toBeTrue();
});
