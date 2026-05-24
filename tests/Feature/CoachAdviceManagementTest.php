<?php

use App\Livewire\Coach\Advice\Index as CoachAdviceIndex;
use App\Livewire\Coach\Players\Show;
use App\Mail\CoachAdviceWritten;
use App\Models\CoachNote;
use App\Models\Player;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
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

test('coach kan advies bewerken', function () {
    Mail::fake();

    $coach = User::factory()->coach()->create();
    $player = coachAdviceManagementPlayer();
    $coachNote = CoachNote::query()->create([
        'player_id' => $player->id,
        'coach_user_id' => $coach->id,
        'week_start_date' => now()->startOfWeek()->toDateString(),
        'type' => 'advice',
        'title' => 'Oud advies',
        'body' => 'De oude tekst.',
        'visible_to_player' => false,
    ]);

    Livewire::actingAs($coach)
        ->test(CoachAdviceIndex::class)
        ->assertSee('Bewerk')
        ->call('edit', $coachNote->id)
        ->assertSet('editingNoteId', $coachNote->id)
        ->assertSet('editingTitle', 'Oud advies')
        ->assertSet('editingBody', 'De oude tekst.')
        ->set('editingTitle', 'Nieuw advies')
        ->set('editingBody', 'De bijgewerkte tekst voor deze week.')
        ->set('editingVisibleToPlayer', true)
        ->call('update')
        ->assertSet('editingNoteId', null)
        ->assertDispatched('advice-updated');

    $coachNote->refresh();

    expect($coachNote->title)->toBe('Nieuw advies')
        ->and($coachNote->body)->toBe('De bijgewerkte tekst voor deze week.')
        ->and($coachNote->visible_to_player)->toBeTrue()
        ->and($coachNote->sent_at)->not->toBeNull();

    Mail::assertQueued(CoachAdviceWritten::class);
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

test('speler kan advies niet bewerken', function () {
    $coach = User::factory()->coach()->create();
    $player = coachAdviceManagementPlayer();
    $coachNote = CoachNote::query()->create([
        'player_id' => $player->id,
        'coach_user_id' => $coach->id,
        'week_start_date' => now()->startOfWeek()->toDateString(),
        'type' => 'advice',
        'title' => 'Coachadvies',
        'body' => 'Dit advies blijft ongewijzigd.',
        'visible_to_player' => true,
        'sent_at' => now(),
    ]);

    Livewire::actingAs($player->user)
        ->test(CoachAdviceIndex::class)
        ->call('edit', $coachNote->id)
        ->assertForbidden();

    expect($coachNote->refresh()->body)->toBe('Dit advies blijft ongewijzigd.');
});

test('coach kan advies bij speler bewerken', function () {
    $coach = User::factory()->coach()->create();
    $player = coachAdviceManagementPlayer();
    $coachNote = CoachNote::query()->create([
        'player_id' => $player->id,
        'coach_user_id' => $coach->id,
        'week_start_date' => now()->startOfWeek()->toDateString(),
        'type' => 'advice',
        'title' => 'Oud spelersadvies',
        'body' => 'Oude tekst op speler.',
        'visible_to_player' => false,
    ]);

    Mail::fake();

    Livewire::actingAs($coach)
        ->test(Show::class, ['player' => $player])
        ->assertSee('Bewerk')
        ->call('editAdvice', $coachNote->id)
        ->assertSet('editingNoteId', $coachNote->id)
        ->assertSet('editingNoteTitle', 'Oud spelersadvies')
        ->assertSet('editingNoteBody', 'Oude tekst op speler.')
        ->set('editingNoteTitle', 'Nieuw spelersadvies')
        ->set('editingNoteBody', 'Nieuwe tekst op speler.')
        ->set('editingNoteVisibleToPlayer', true)
        ->call('updateAdvice')
        ->assertSet('editingNoteId', null)
        ->assertDispatched('advice-updated');

    $coachNote->refresh();

    expect($coachNote->title)->toBe('Nieuw spelersadvies')
        ->and($coachNote->body)->toBe('Nieuwe tekst op speler.')
        ->and($coachNote->visible_to_player)->toBeTrue()
        ->and($coachNote->sent_at)->not->toBeNull();

    Mail::assertQueued(CoachAdviceWritten::class);
});

test('coach kan advies bij speler verwijderen', function () {
    $coach = User::factory()->coach()->create();
    $player = coachAdviceManagementPlayer();
    $coachNote = CoachNote::query()->create([
        'player_id' => $player->id,
        'coach_user_id' => $coach->id,
        'week_start_date' => now()->startOfWeek()->toDateString(),
        'type' => 'advice',
        'title' => 'Coachadvies',
        'body' => 'Deze speler-notitie mag weg.',
        'visible_to_player' => true,
        'sent_at' => now(),
    ]);

    Livewire::actingAs($coach)
        ->test(Show::class, ['player' => $player])
        ->assertSee('Deze speler-notitie mag weg.')
        ->call('deleteAdvice', $coachNote->id)
        ->assertDispatched('advice-deleted');

    expect(CoachNote::query()->whereKey($coachNote->id)->exists())->toBeFalse()
        ->and(Player::query()->whereKey($player->id)->exists())->toBeTrue();
});

test('speler kan advies niet aanpassen vanaf spelersdetail', function () {
    $coach = User::factory()->coach()->create();
    $player = coachAdviceManagementPlayer();
    $coachNote = CoachNote::query()->create([
        'player_id' => $player->id,
        'coach_user_id' => $coach->id,
        'week_start_date' => now()->startOfWeek()->toDateString(),
        'type' => 'advice',
        'title' => 'Coachadvies',
        'body' => 'Alleen de coach mag dit aanpassen.',
        'visible_to_player' => true,
        'sent_at' => now(),
    ]);

    Livewire::actingAs($player->user)
        ->test(Show::class, ['player' => $player])
        ->call('editAdvice', $coachNote->id)
        ->assertForbidden();

    expect($coachNote->refresh()->body)->toBe('Alleen de coach mag dit aanpassen.');
});
