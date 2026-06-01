<?php

use App\Livewire\Player\Checkin;
use App\Mail\PlayerCheckinSubmitted;
use App\Models\Player;
use App\Models\User;
use App\Models\WeeklyCheckin;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

function checkinMailPlayer(array $attributes = []): Player
{
    $user = User::factory()->player()->create();

    $player = Player::factory()->create($attributes + [
        'name' => 'Sem Checkin',
        'user_id' => $user->id,
        'program_type' => Player::Maintenance,
    ]);

    $player->settings()->create([
        'strength_target_per_week' => 2,
        'conditioning_target_per_week' => 2,
        'mobility_target_per_week' => 3,
    ]);

    return $player;
}

function submitMaintenanceCheckin(Player $player): void
{
    Livewire::actingAs($player->user)
        ->test(Checkin::class)
        ->set('selectedWeekStartDate', now()->startOfWeek()->toDateString())
        ->set('form.strength_sessions', 2)
        ->set('form.conditioning_sessions', 2)
        ->set('form.mobility_sessions', 3)
        ->set('form.had_full_rest_day', true)
        ->set('form.sleep_avg_hours', 7.5)
        ->set('form.energy_score', 8)
        ->set('form.soreness_score', 3)
        ->set('form.total_training_minutes', 120)
        ->set('form.highest_session_rpe', 6)
        ->set('form.notes', 'Voelde goed, vooral donderdag.')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('saved', true);
}

test('coach krijgt een mail wanneer speler de weekcheck verstuurt', function () {
    Mail::fake();

    $coach = User::factory()->coach()->create(['email' => 'coach@example.test']);
    $player = checkinMailPlayer();

    submitMaintenanceCheckin($player);

    $checkin = WeeklyCheckin::query()->whereBelongsTo($player)->firstOrFail();

    expect($checkin->submitted_at)->not->toBeNull()
        ->and($checkin->coach_notified_at)->not->toBeNull();

    Mail::assertSent(PlayerCheckinSubmitted::class, function (PlayerCheckinSubmitted $mail) use ($checkin, $coach): bool {
        return $mail->hasTo($coach->email)
            && $mail->weeklyCheckin->is($checkin)
            && $mail->coach->is($coach);
    });
});

test('autosave stuurt nog geen coachmail', function () {
    Mail::fake();

    User::factory()->coach()->create(['email' => 'coach@example.test']);
    $player = checkinMailPlayer();

    Livewire::actingAs($player->user)
        ->test(Checkin::class)
        ->set('form.strength_sessions', 2);

    $checkin = WeeklyCheckin::query()->whereBelongsTo($player)->firstOrFail();

    expect($checkin->submitted_at)->toBeNull()
        ->and($checkin->coach_notified_at)->toBeNull();

    Mail::assertNothingSent();
});

test('coachmail wordt maar een keer per checkin verstuurd', function () {
    Mail::fake();

    User::factory()->coach()->create(['email' => 'coach@example.test']);
    $player = checkinMailPlayer();

    submitMaintenanceCheckin($player);
    submitMaintenanceCheckin($player);

    $checkin = WeeklyCheckin::query()->whereBelongsTo($player)->firstOrFail();

    expect($checkin->coach_notified_at)->not->toBeNull();

    Mail::assertSentCount(1);
});

test('coachmail bevat checkin summary en links', function () {
    $coach = User::factory()->coach()->create(['name' => 'Coach Rak']);
    $player = checkinMailPlayer(['name' => 'Milan Bulk', 'program_type' => Player::MuscleGain]);

    $checkin = WeeklyCheckin::query()->create([
        'player_id' => $player->id,
        'week_start_date' => now()->startOfWeek()->toDateString(),
        'weight_kg' => 64.2,
        'strength_sessions' => 3,
        'conditioning_sessions' => 1,
        'mobility_sessions' => 3,
        'pickup_monday' => true,
        'pickup_thursday' => null,
        'had_full_rest_day' => true,
        'sleep_avg_hours' => 7.5,
        'energy_score' => 8,
        'soreness_score' => 3,
        'pain' => false,
        'total_training_minutes' => 120,
        'highest_session_rpe' => 6,
        'calculated_training_load' => 720,
        'kcal_avg' => 3300,
        'protein_status' => 'partial',
        'protein_avg_grams' => 115,
        'protein_target_days' => 4,
        'protein_notes' => 'Lunch eiwit nog wisselend.',
        'appetite_score' => 6,
        'notes' => 'Voelde goed, vooral donderdag.',
        'submitted_at' => now(),
    ]);

    $mailable = new PlayerCheckinSubmitted($checkin, $coach);

    $mailable->assertSeeInHtml('Nieuwe weekcheck');
    $mailable->assertSeeInHtml('Milan Bulk');
    $mailable->assertSeeInHtml('3 kracht, 1 conditie/pickup, 3 preventie/mobiliteit');
    $mailable->assertSeeInHtml('Gem. kcal');
    $mailable->assertSeeInHtml('Lunch eiwit nog wisselend.');
    $mailable->assertSeeInHtml('Voelde goed, vooral donderdag.');
    $mailable->assertSeeInHtml('Bekijk check-in');
    $mailable->assertSeeInHtml(route('coach.checkins.show', $checkin));
    $mailable->assertSeeInHtml(route('coach.players.show', $player));
});
