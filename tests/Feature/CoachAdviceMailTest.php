<?php

use App\Livewire\Coach\Advice\Index as CoachAdviceIndex;
use App\Livewire\Coach\Players\Show;
use App\Mail\CoachAdviceWritten;
use App\Models\CoachNote;
use App\Models\Player;
use App\Models\User;
use App\Models\WeeklyCheckin;
use Illuminate\Support\Carbon;
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

    Mail::assertSent(CoachAdviceWritten::class, function (CoachAdviceWritten $mail) use ($coachNote, $player): bool {
        return $mail->hasTo($player->user->email)
            && $mail->coachNote->is($coachNote);
    });
});

test('coach kan advies schrijven voor een gekozen vorige week', function () {
    $this->travelTo(Carbon::parse('2026-06-01 12:00'));

    Mail::fake();

    $coach = User::factory()->coach()->create();
    $player = coachAdviceMailPlayer();
    $previousWeek = now()->startOfWeek()->subWeek();

    WeeklyCheckin::query()->create([
        'player_id' => $player->id,
        'week_start_date' => $previousWeek->toDateString(),
        'strength_sessions' => 2,
        'conditioning_sessions' => 2,
        'mobility_sessions' => 3,
        'had_full_rest_day' => true,
        'sleep_avg_hours' => 7.5,
        'energy_score' => 8,
        'soreness_score' => 3,
        'submitted_at' => $previousWeek->copy()->endOfWeek(),
    ]);

    Livewire::actingAs($coach)
        ->test(Show::class, ['player' => $player])
        ->assertSet('adviceWeek', '2026-W22')
        ->assertSee('Week 22')
        ->assertSee('Gebaseerd op de ingediende weekcheck')
        ->assertDontSee('Vul de weekcheck in zodat we gericht kunnen bijsturen.')
        ->set('adviceBody', 'Vorige week zag er stabiel uit; ritme vasthouden.')
        ->set('visibleToPlayer', true)
        ->call('saveAdvice')
        ->assertDispatched('advice-saved');

    $coachNote = CoachNote::query()->whereBelongsTo($player)->firstOrFail();

    expect($coachNote->week_start_date->toDateString())->toBe('2026-05-25')
        ->and($coachNote->body)->toBe('Vorige week zag er stabiel uit; ritme vasthouden.')
        ->and($coachNote->visible_to_player)->toBeTrue();

    Mail::assertSent(CoachAdviceWritten::class);
});

test('coach kan de adviesweek wisselen naar deze week', function () {
    $this->travelTo(Carbon::parse('2026-06-01 12:00'));

    $coach = User::factory()->coach()->create();
    $player = coachAdviceMailPlayer();
    $previousWeek = now()->startOfWeek()->subWeek();

    WeeklyCheckin::query()->create([
        'player_id' => $player->id,
        'week_start_date' => $previousWeek->toDateString(),
        'strength_sessions' => 2,
        'conditioning_sessions' => 2,
        'mobility_sessions' => 3,
        'had_full_rest_day' => true,
        'sleep_avg_hours' => 7.5,
        'energy_score' => 8,
        'soreness_score' => 3,
        'submitted_at' => $previousWeek->copy()->endOfWeek(),
    ]);

    Livewire::actingAs($coach)
        ->test(Show::class, ['player' => $player])
        ->assertSet('adviceWeek', '2026-W22')
        ->call('currentAdviceWeek')
        ->assertSet('adviceWeek', '2026-W23')
        ->assertSee('Geen ingediende weekcheck voor deze adviesweek')
        ->assertSet('adviceBody', 'Vul de weekcheck in zodat we gericht kunnen bijsturen.');
});

test('adviesmail gebruikt de Flashing mailtemplate', function () {
    $coach = User::factory()->coach()->create(['name' => 'Coach Rak']);
    $player = coachAdviceMailPlayer();
    $coachNote = CoachNote::query()->create([
        'player_id' => $player->id,
        'coach_user_id' => $coach->id,
        'week_start_date' => now()->startOfWeek()->toDateString(),
        'type' => 'advice',
        'title' => 'Coachadvies',
        'body' => 'Pak deze week twee rustige krachtmomenten.',
        'visible_to_player' => true,
        'sent_at' => now(),
    ]);

    $mailable = new CoachAdviceWritten($coachNote);

    $mailable->assertSeeInHtml('logo-email-white.png');
    $mailable->assertSeeInHtml('Nieuw coachadvies');
    $mailable->assertSeeInHtml('Pak deze week twee rustige krachtmomenten.');
    $mailable->assertSeeInHtml('Bekijk advies');

    $html = $mailable->render();

    expect($html)
        ->toContain('background-color: #df6e28')
        ->toContain('padding: 10px 18px')
        ->toContain('border: 1px solid #df6e28')
        ->not->toContain('border-left: 18px solid #df6e28');
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

    Mail::assertNothingSent();
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

    Mail::assertSentCount(1);

    Livewire::actingAs($coach)
        ->test(CoachAdviceIndex::class)
        ->call('toggleVisible', $coachNote->id)
        ->call('toggleVisible', $coachNote->id);

    expect($coachNote->refresh()->visible_to_player)->toBeTrue()
        ->and($coachNote->sent_at)->not->toBeNull();

    Mail::assertSentCount(1);
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

    Mail::assertNothingSent();
});
