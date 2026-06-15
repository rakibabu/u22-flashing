<?php

use App\Livewire\Coach\AnalysisExport;
use App\Livewire\Coach\Dashboard;
use App\Livewire\Coach\Players\CheckinPreview;
use App\Livewire\Coach\Players\Create;
use App\Livewire\Coach\Players\Edit;
use App\Livewire\Coach\Players\Index;
use App\Livewire\Coach\Players\Show;
use App\Livewire\Player\Checkin;
use App\Models\CoachNote;
use App\Models\ExerciseLibraryItem;
use App\Models\Invite;
use App\Models\Player;
use App\Models\ProgramPhase;
use App\Models\ProgramTemplate;
use App\Models\TestResult;
use App\Models\User;
use App\Models\WeeklyCheckin;
use App\Services\PlayerAdviceService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function coachUser(): User
{
    return User::factory()->coach()->create(['email' => 'coach@example.test']);
}

function playerWithUser(array $playerAttributes = []): Player
{
    $user = User::factory()->player()->create();

    $player = Player::factory()->create($playerAttributes + [
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

/**
 * @return array<string, mixed>
 */
function completeCheckinFormFor(Player $player): array
{
    $form = [
        'form.strength_sessions' => $player->isMuscleGain() ? 3 : 2,
        'form.conditioning_sessions' => $player->isMuscleGain() ? 1 : 2,
        'form.mobility_sessions' => 3,
        'form.pickup_monday' => true,
        'form.had_full_rest_day' => true,
        'form.total_training_minutes' => 120,
        'form.highest_session_rpe' => 6,
        'form.sleep_avg_hours' => 7.5,
        'form.energy_score' => 8,
        'form.soreness_score' => 3,
        'form.notes' => 'Volledige deploy-check voor dit programma.',
    ];

    if ($player->tracksNutrition()) {
        $form += [
            'form.weight_kg' => $player->isGuardDevelopment() ? 70.2 : 61.4,
            'form.kcal_avg' => $player->isGuardDevelopment() ? 3100 : 3300,
            'form.protein_target_days' => 7,
            'form.appetite_score' => 7,
            'form.used_mijn_eetmeter' => true,
        ];
    }

    if ($player->isGuardDevelopment()) {
        $form += [
            'form.handle_sessions' => 3,
            'form.handle_minutes' => 90,
            'form.handles_worked_on' => 'Pound, retreat, cross, pressure passing.',
            'form.pickup_sessions' => 2,
            'form.pickup_thursday' => true,
            'form.conditioning_minutes' => 60,
            'form.defence_sessions' => 2,
            'form.playbook_calls_learned' => 1,
            'form.playbook_focus' => 'Horns entry en eerste reset.',
        ];
    }

    return $form;
}

test('coach kan speler aanmaken', function () {
    Livewire::actingAs(coachUser())
        ->test(Create::class)
        ->set('name', 'Nieuwe Speler')
        ->set('program_type', Player::Conditioning)
        ->call('save')
        ->assertRedirect();

    expect(Player::query()->where('name', 'Nieuwe Speler')->exists())->toBeTrue();

    $player = Player::query()->where('name', 'Nieuwe Speler')->with('settings')->firstOrFail();

    expect($player->settings->strength_target_per_week)->toBe(2)
        ->and($player->settings->conditioning_target_per_week)->toBe(2)
        ->and($player->settings->mobility_target_per_week)->toBe(3);
});

test('coach maakt muscle gain speler aan met persoonlijke bulk defaults', function () {
    Livewire::actingAs(coachUser())
        ->test(Create::class)
        ->set('name', 'Nieuwe Bulk')
        ->set('program_type', Player::MuscleGain)
        ->set('age', 21)
        ->set('height_cm', 183)
        ->set('start_weight_kg', 60)
        ->call('save')
        ->assertRedirect();

    $player = Player::query()->where('name', 'Nieuwe Bulk')->with('settings')->firstOrFail();

    expect($player->settings->strength_target_per_week)->toBe(3)
        ->and($player->settings->conditioning_target_per_week)->toBe(1)
        ->and($player->settings->pickup_monday_expected)->toBeTrue()
        ->and($player->settings->pickup_thursday_expected)->toBeFalse()
        ->and($player->settings->kcal_minimum)->toBe(3000)
        ->and($player->settings->kcal_training_day)->toBe(3400)
        ->and($player->settings->kcal_pickup_day)->toBe(3600)
        ->and($player->settings->protein_target_min)->toBe(120)
        ->and($player->settings->notes)->toContain('3x kracht');
});

test('coach maakt guard development speler aan met jos defaults', function () {
    Livewire::actingAs(coachUser())
        ->test(Create::class)
        ->set('name', 'Jos Guard')
        ->set('program_type', Player::GuardDevelopment)
        ->call('save')
        ->assertRedirect();

    $player = Player::query()->where('name', 'Jos Guard')->with('settings')->firstOrFail();

    expect($player->programName())->toBe('Guard development')
        ->and($player->settings->strength_target_per_week)->toBe(2)
        ->and($player->settings->conditioning_target_per_week)->toBe(2)
        ->and($player->settings->handle_sessions_target_per_week)->toBe(3)
        ->and($player->settings->handle_minutes_target_per_week)->toBe(75)
        ->and($player->settings->pickup_target_per_week)->toBe(1)
        ->and($player->settings->conditioning_minutes_target_per_week)->toBe(50)
        ->and($player->settings->defence_sessions_target_per_week)->toBe(2)
        ->and($player->settings->playbook_calls_target_per_week)->toBe(1)
        ->and($player->settings->kcal_minimum)->toBe(2800)
        ->and($player->settings->protein_target_min)->toBe(120)
        ->and($player->settings->notes)->toContain('Guard development');
});

test('coach kan speler verwijderen met gekoppelde spelerdata', function () {
    $coach = coachUser();
    $player = playerWithUser(['name' => 'Te Verwijderen Speler']);
    $userId = $player->user_id;
    [$invite] = Invite::createForPlayer($player);

    $checkin = WeeklyCheckin::query()->create([
        'player_id' => $player->id,
        'week_start_date' => now()->startOfWeek()->toDateString(),
    ]);
    $testResult = TestResult::query()->create([
        'player_id' => $player->id,
        'test_date' => now()->toDateString(),
    ]);
    $coachNote = CoachNote::query()->create([
        'player_id' => $player->id,
        'coach_user_id' => $coach->id,
        'title' => 'Coachnotitie',
        'body' => 'Deze notitie hoort bij de speler.',
    ]);
    $settingId = $player->settings->id;

    Livewire::actingAs($coach)
        ->test(Index::class)
        ->assertSee('Te Verwijderen Speler')
        ->assertSee('Verwijder')
        ->call('deletePlayer', $player->id)
        ->assertDispatched('player-deleted')
        ->assertDontSee('Te Verwijderen Speler');

    expect(Player::query()->whereKey($player->id)->exists())->toBeFalse()
        ->and(WeeklyCheckin::query()->whereKey($checkin->id)->exists())->toBeFalse()
        ->and(Invite::query()->whereKey($invite->id)->exists())->toBeFalse()
        ->and(TestResult::query()->whereKey($testResult->id)->exists())->toBeFalse()
        ->and(CoachNote::query()->whereKey($coachNote->id)->exists())->toBeFalse()
        ->and(DB::table('player_program_settings')->whereKey($settingId)->exists())->toBeFalse()
        ->and(User::query()->whereKey($userId)->exists())->toBeTrue();
});

test('speler kan geen speler verwijderen', function () {
    $ownPlayer = playerWithUser(['name' => 'Eigen Speler']);
    $otherPlayer = playerWithUser(['name' => 'Andere Speler']);

    Livewire::actingAs($ownPlayer->user)
        ->test(Index::class)
        ->call('deletePlayer', $otherPlayer->id)
        ->assertForbidden();

    expect(Player::query()->whereKey($otherPlayer->id)->exists())->toBeTrue();
});

test('coach kan speler naar ander programma switchen met nieuwe standaard targets', function () {
    $coach = coachUser();
    $player = playerWithUser(['name' => 'Switch Speler', 'program_type' => Player::Maintenance]);

    Livewire::actingAs($coach)
        ->test(Edit::class, ['player' => $player])
        ->assertSet('form.program_type', Player::Maintenance)
        ->assertSee('targets automatisch aangepast')
        ->set('form.program_type', Player::GuardDevelopment)
        ->call('save')
        ->assertRedirect(route('coach.players.show', $player));

    $player->refresh()->load('settings');

    expect($player->program_type)->toBe(Player::GuardDevelopment)
        ->and($player->settings->handle_sessions_target_per_week)->toBe(3)
        ->and($player->settings->handle_minutes_target_per_week)->toBe(75)
        ->and($player->settings->pickup_target_per_week)->toBe(1)
        ->and($player->settings->conditioning_minutes_target_per_week)->toBe(50)
        ->and($player->settings->defence_sessions_target_per_week)->toBe(2)
        ->and($player->settings->playbook_calls_target_per_week)->toBe(1)
        ->and($player->settings->kcal_minimum)->toBe(2800)
        ->and($player->settings->protein_target_min)->toBe(120)
        ->and($player->settings->notes)->toContain('Guard development');

    Livewire::actingAs($coach)
        ->test(Edit::class, ['player' => $player])
        ->set('form.program_type', Player::Maintenance)
        ->call('save')
        ->assertRedirect(route('coach.players.show', $player));

    $player->refresh()->load('settings');

    expect($player->program_type)->toBe(Player::Maintenance)
        ->and($player->settings->strength_target_per_week)->toBe(2)
        ->and($player->settings->conditioning_target_per_week)->toBe(2)
        ->and($player->settings->handle_sessions_target_per_week)->toBeNull()
        ->and($player->settings->handle_minutes_target_per_week)->toBeNull()
        ->and($player->settings->pickup_target_per_week)->toBeNull()
        ->and($player->settings->kcal_minimum)->toBeNull()
        ->and($player->settings->protein_target_min)->toBeNull()
        ->and($player->settings->notes)->toBeNull();
});

test('coach bewaart aangepaste targets als programma niet wijzigt', function () {
    $player = playerWithUser(['name' => 'Custom Target Speler', 'program_type' => Player::Maintenance]);
    $player->settings()->update([
        'strength_target_per_week' => 4,
        'conditioning_target_per_week' => 1,
        'notes' => 'Bewaar deze persoonlijke targets.',
    ]);

    Livewire::actingAs(coachUser())
        ->test(Edit::class, ['player' => $player])
        ->set('form.name', 'Custom Target Speler Updated')
        ->call('save')
        ->assertRedirect(route('coach.players.show', $player));

    $player->refresh()->load('settings');

    expect($player->name)->toBe('Custom Target Speler Updated')
        ->and($player->program_type)->toBe(Player::Maintenance)
        ->and($player->settings->strength_target_per_week)->toBe(4)
        ->and($player->settings->conditioning_target_per_week)->toBe(1)
        ->and($player->settings->notes)->toBe('Bewaar deze persoonlijke targets.');
});

test('coach kan trainingsprogramma pdf per type uploaden', function () {
    Storage::fake('local');
    $coach = coachUser();

    $template = ProgramTemplate::query()->create([
        'type' => Player::Conditioning,
        'name' => 'Trainingstype A: Conditie',
        'description' => 'Conditieprogramma',
        'goal' => 'Fit worden',
        'sort_order' => 1,
    ]);
    $pdf = UploadedFile::fake()->create('programma.pdf', 20, 'application/pdf');

    $this->actingAs($coach)->post(route('coach.program-templates.pdf.store', $template), [
        'training_program_pdf' => $pdf,
    ])->assertRedirect()
        ->assertSessionHas('saved_program_template_id', $template->id);

    $template->refresh();

    expect($template->training_program_pdf_path)->not->toBeNull();
    Storage::disk('local')->assertExists($template->training_program_pdf_path);
});

test('coach kan ontbrekende standaard programma templates aanmaken', function () {
    expect(ProgramTemplate::query()->count())->toBe(0);

    Livewire::actingAs(coachUser())
        ->test(Index::class)
        ->assertSee("Standaard programma's aanmaken", false)
        ->call('createDefaultProgramTemplates')
        ->assertSet('programTemplatesCreated', true)
        ->assertSee('Trainingstype A: Conditie')
        ->assertSee('Trainingstype B: Bulk, kracht en spiermassa')
        ->assertSee('Trainingstype C: Conditie en kracht onderhoud')
        ->assertSee('Trainingstype D: Guard development');

    expect(ProgramTemplate::query()->count())->toBe(4)
        ->and(ProgramTemplate::query()->where('type', Player::Conditioning)->exists())->toBeTrue()
        ->and(ProgramTemplate::query()->where('type', Player::MuscleGain)->exists())->toBeTrue()
        ->and(ProgramTemplate::query()->where('type', Player::Maintenance)->exists())->toBeTrue()
        ->and(ProgramTemplate::query()->where('type', Player::GuardDevelopment)->exists())->toBeTrue();
});

test('invite-link werkt een keer en speler kan wachtwoord instellen', function () {
    $player = Player::factory()->create(['name' => 'Invite Speler', 'program_type' => Player::Maintenance]);
    [$invite, $token] = Invite::createForPlayer($player);

    $this->get(route('invite.show', $token))->assertOk()->assertSee($player->name);

    $this->post(route('invite.store', $token), [
        'username' => 'speler-one',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('player.home', absolute: false));

    $this->assertAuthenticated();
    expect($invite->refresh()->used_at)->not->toBeNull();

    $this->post(route('logout'));
    $this->get(route('invite.show', $token))->assertNotFound();
});

test('verlopen invite werkt niet', function () {
    $player = Player::factory()->create(['program_type' => Player::Maintenance]);
    $token = 'expired-token';

    $player->invites()->create([
        'token_hash' => hash('sha256', $token),
        'expires_at' => now()->subDay(),
    ]);

    $this->get(route('invite.show', $token))->assertNotFound();
});

test('speler ziet alleen eigen data', function () {
    $own = playerWithUser(['name' => 'Eigen Speler']);
    $other = playerWithUser(['name' => 'Andere Speler']);

    $this->actingAs($own->user)->get(route('player.progress'))
        ->assertOk()
        ->assertSee('Voortgang')
        ->assertDontSee($other->name);

    $this->actingAs($own->user)->get(route('coach.players.show', $other))->assertForbidden();
});

test('coach ziet dashboard', function () {
    playerWithUser(['name' => 'Dashboard Speler']);

    $this->actingAs(coachUser())->get(route('coach.dashboard'))
        ->assertOk()
        ->assertSee('Coach dashboard')
        ->assertSee('u22-sidebar')
        ->assertSee('/images/flashing/logo-white.svg');
});

test('coach kan een volledige check-in bekijken', function () {
    $player = playerWithUser(['name' => 'Checkin Detail Speler']);
    $checkin = WeeklyCheckin::query()->create([
        'player_id' => $player->id,
        'week_start_date' => now()->startOfWeek()->toDateString(),
        'strength_sessions' => 2,
        'conditioning_sessions' => 1,
        'mobility_sessions' => 3,
        'had_full_rest_day' => false,
        'sleep_avg_hours' => 7.5,
        'energy_score' => 8,
        'total_training_minutes' => 120,
        'highest_session_rpe' => 7,
        'calculated_training_load' => 840,
        'notes' => 'Goede week, benen waren fris.',
        'submitted_at' => now(),
    ]);

    $this->actingAs(coachUser())->get(route('coach.checkins.show', $checkin))
        ->assertOk()
        ->assertSee('Check-in Checkin Detail Speler')
        ->assertSee('Training load')
        ->assertSee('840')
        ->assertSee('Volledige rustdag')
        ->assertSee('Nee')
        ->assertSee('Goede week, benen waren fris.');
});

test('coach check-in detail berekent compliance voor de check-in week', function () {
    $this->travelTo(Carbon::parse('2026-06-08 17:04'));

    $player = playerWithUser(['name' => 'Vorige Week Speler']);
    $checkin = WeeklyCheckin::query()->create([
        'player_id' => $player->id,
        'week_start_date' => Carbon::parse('2026-06-01')->toDateString(),
        'strength_sessions' => 2,
        'conditioning_sessions' => 2,
        'mobility_sessions' => 3,
        'had_full_rest_day' => true,
        'sleep_avg_hours' => 7.5,
        'energy_score' => 8,
        'soreness_score' => 3,
        'pain' => false,
        'total_training_minutes' => 120,
        'highest_session_rpe' => 7,
        'calculated_training_load' => 840,
        'submitted_at' => Carbon::parse('2026-06-08 09:28'),
    ]);

    $this->actingAs(coachUser())->get(route('coach.checkins.show', $checkin))
        ->assertOk()
        ->assertSee('Check-in Vorige Week Speler')
        ->assertSee('100%')
        ->assertSee('Op schema')
        ->assertDontSee('Check-in mist deze week');
});

test('coach ziet weekchecks van alle spelers en weken', function () {
    $first = playerWithUser(['name' => 'Eerste Weekspeler']);
    $second = playerWithUser(['name' => 'Tweede Weekspeler']);

    WeeklyCheckin::query()->create([
        'player_id' => $first->id,
        'week_start_date' => now()->startOfWeek()->subWeek()->toDateString(),
        'strength_sessions' => 2,
        'conditioning_sessions' => 2,
        'mobility_sessions' => 3,
        'submitted_at' => now(),
    ]);

    WeeklyCheckin::query()->create([
        'player_id' => $second->id,
        'week_start_date' => now()->startOfWeek()->toDateString(),
        'strength_sessions' => 1,
        'conditioning_sessions' => 2,
        'mobility_sessions' => 3,
        'submitted_at' => now(),
    ]);

    $this->actingAs(coachUser())->get(route('coach.checkins.index'))
        ->assertOk()
        ->assertSee('Eerste Weekspeler')
        ->assertSee('Tweede Weekspeler')
        ->assertSee(now()->startOfWeek()->subWeek()->format('d-m-Y'))
        ->assertSee(now()->startOfWeek()->format('d-m-Y'));
});

test('speler ziet persoonlijke programma pdf en geen lege oefenbibliotheek', function () {
    Storage::fake('local');

    $player = playerWithUser(['name' => 'Programma Pdf Speler', 'program_type' => Player::Conditioning]);
    $template = ProgramTemplate::query()->create([
        'type' => Player::Conditioning,
        'name' => 'Trainingstype A: Conditie',
        'description' => 'Conditieprogramma',
        'goal' => 'Fit worden',
        'sort_order' => 1,
        'training_program_pdf_path' => 'program-templates/conditioning/programma.pdf',
    ]);

    Storage::disk('local')->put($template->training_program_pdf_path, '%PDF-1.4 test');

    $this->actingAs($player->user)->get(route('player.program'))
        ->assertOk()
        ->assertSee('Jouw persoonlijke trainingsprogramma')
        ->assertSee(route('player.program.pdf'), false)
        ->assertDontSee('Oefenbibliotheek');

    $this->actingAs($player->user)->get(route('player.program.pdf'))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('coach kan speler weekcheck scherm previewen zonder speleraccount te maken', function () {
    $coach = coachUser();
    $player = playerWithUser(['name' => 'Preview Bulk', 'program_type' => Player::MuscleGain]);
    $player->settings()->update([
        'strength_target_per_week' => 3,
        'conditioning_target_per_week' => 1,
        'mobility_target_per_week' => 3,
        'kcal_minimum' => 3000,
        'kcal_training_day' => 3400,
        'protein_target_min' => 120,
        'protein_target_max' => 130,
    ]);

    $this->actingAs($coach)->get(route('coach.players.checkin-preview', $player))
        ->assertOk()
        ->assertSee('Weekcheck preview')
        ->assertSee('Preview Bulk')
        ->assertSee('u22-form-card')
        ->assertSee('u22-target-summary')
        ->assertSee('Stap 1 van 4')
        ->assertSee('Training')
        ->assertSee('Aantal keer kracht')
        ->assertSee('Aantal keer extra conditie')
        ->assertSee('Pickup donderdag')
        ->assertSee('Alleen invullen als je meedeed')
        ->assertDontSee('Donderdagpickup staat niet in jouw spiermassa-plan')
        ->assertSee('u22-checkin-draft:preview:'.$player->id, false)
        ->assertSee('restoreDraft()', false)
        ->assertDontSee('Gemiddelde kcal per dag');

    Livewire::actingAs($coach)
        ->test(CheckinPreview::class, ['player' => $player])
        ->assertSet('form.strength_sessions', null)
        ->assertSet('form.conditioning_sessions', null)
        ->assertSet('form.mobility_sessions', null)
        ->assertSee('Pickup donderdag')
        ->assertDontSee('Donderdagpickup staat niet in jouw spiermassa-plan')
        ->assertSee('u22-choice-grid-count', false)
        ->assertSee('4+')
        ->call('nextStep')
        ->assertSet('step', 1)
        ->assertHasErrors([
            'form.strength_sessions' => 'required',
            'form.conditioning_sessions' => 'required',
            'form.mobility_sessions' => 'required',
        ])
        ->assertSet('validationScrollField', 'strength_sessions')
        ->assertSee('u22-field-error', false)
        ->assertSee('data-checkin-field="strength_sessions"', false)
        ->set('form.strength_sessions', 3)
        ->set('form.conditioning_sessions', 1)
        ->set('form.mobility_sessions', 3)
        ->set('form.total_training_minutes', 90)
        ->set('form.highest_session_rpe', 6)
        ->call('nextStep')
        ->assertSet('step', 2)
        ->assertSee('Aantal uur slaap')
        ->assertSee('Spierpijn')
        ->assertSee('Licht - zwaar')
        ->assertDontSee('Spierpijn/vermoeidheid')
        ->assertDontSee('1 fris, 10 heel zwaar')
        ->assertSee('u22-sleep-field', false)
        ->assertSee('u22-choice-grid-score', false)
        ->call('goToStep', 99)
        ->assertSet('step', 2)
        ->assertHasErrors([
            'form.sleep_avg_hours' => 'required',
            'form.energy_score' => 'required',
            'form.soreness_score' => 'required',
        ])
        ->assertSet('validationScrollField', 'sleep_avg_hours')
        ->set('form.sleep_avg_hours', 7.5)
        ->set('form.energy_score', 7)
        ->set('form.soreness_score', 4)
        ->call('goToStep', 99)
        ->assertSet('step', 3)
        ->assertHasErrors([
            'form.weight_kg' => 'required',
            'form.kcal_avg' => 'required',
            'form.protein_target_days' => 'required',
            'form.appetite_score' => 'required',
        ])
        ->assertSet('validationScrollField', 'weight_kg')
        ->assertSee('Kcal gemiddeld')
        ->assertSee('u22-sleep-field', false)
        ->assertDontSee('u22-number-tile', false)
        ->assertSee('Hoeveel dagen haalde je')
        ->set('form.weight_kg', 60.5)
        ->set('form.kcal_avg', 3200)
        ->set('form.protein_target_days', 7)
        ->set('form.appetite_score', 6)
        ->call('nextStep')
        ->assertSet('step', 4)
        ->assertSee('Opslaan (preview)')
        ->assertSee('Controleer je week')
        ->assertSee('4/10')
        ->assertDontSee('matige spierpijn')
        ->assertSee('Je zit op schema')
        ->assertDontSee('De coach ziet hieronder precies waar het wringt')
        ->assertDontSee('Opmerking over conditie of benen')
        ->assertSee('Extra opmerking voor de coach')
        ->call('previousStep')
        ->call('previousStep')
        ->set('form.pain', true)
        ->assertSet('step', 2)
        ->assertSee('Waar zit de pijn?');
});

test('onderhoudsspeler ziet geen gewicht in checkin preview', function () {
    $coach = coachUser();
    $player = playerWithUser(['name' => 'Preview Onderhoud', 'program_type' => Player::Maintenance]);

    $this->actingAs($coach)->get(route('coach.players.checkin-preview', $player))
        ->assertOk()
        ->assertSee('Stap 1 van 3')
        ->assertSee('Training')
        ->assertDontSee('Gewicht')
        ->assertDontSee('Gemiddelde kcal per dag');

    Livewire::actingAs($coach)
        ->test(CheckinPreview::class, ['player' => $player])
        ->set('form.strength_sessions', 2)
        ->set('form.conditioning_sessions', 2)
        ->set('form.mobility_sessions', 3)
        ->set('form.total_training_minutes', 120)
        ->set('form.highest_session_rpe', 6)
        ->call('nextStep')
        ->set('form.sleep_avg_hours', 7.5)
        ->set('form.energy_score', 7)
        ->set('form.soreness_score', 4)
        ->call('nextStep')
        ->assertSet('step', 3)
        ->assertSee('Afronden')
        ->assertDontSee('Gewicht');
});

test('speler kan coach weekcheck preview niet bekijken', function () {
    $player = playerWithUser(['name' => 'Eigen Preview']);
    $other = playerWithUser(['name' => 'Andere Preview']);

    $this->actingAs($player->user)->get(route('coach.players.checkin-preview', $other))
        ->assertForbidden();
});

test('speler kan coach check-in detail niet bekijken', function () {
    $player = playerWithUser();
    $checkin = WeeklyCheckin::query()->create([
        'player_id' => $player->id,
        'week_start_date' => now()->startOfWeek()->toDateString(),
        'strength_sessions' => 2,
        'conditioning_sessions' => 2,
        'mobility_sessions' => 3,
        'submitted_at' => now(),
    ]);

    $this->actingAs($player->user)->get(route('coach.checkins.show', $checkin))
        ->assertForbidden();
});

test('alle programma types kunnen een volledige weekcheck indienen', function (string $programType, int $expectedMaxStep) {
    $player = playerWithUser(['program_type' => $programType]);

    if ($player->isMuscleGain()) {
        $player->settings()->update([
            'strength_target_per_week' => 3,
            'conditioning_target_per_week' => 1,
            'mobility_target_per_week' => 3,
            'kcal_minimum' => 3000,
            'protein_target_min' => 120,
            'protein_target_max' => 130,
        ]);
    }

    if ($player->isGuardDevelopment()) {
        $player->settings()->update([
            'handle_sessions_target_per_week' => 3,
            'handle_minutes_target_per_week' => 75,
            'pickup_target_per_week' => 1,
            'conditioning_minutes_target_per_week' => 50,
            'defence_sessions_target_per_week' => 2,
            'playbook_calls_target_per_week' => 1,
            'kcal_minimum' => 2800,
            'protein_target_min' => 120,
            'protein_target_max' => 130,
        ]);
    }

    $component = Livewire::actingAs($player->user)
        ->test(Checkin::class)
        ->assertSet('step', 1)
        ->assertSee('Stap 1 van '.$expectedMaxStep);

    if ($player->isGuardDevelopment()) {
        $component
            ->assertDontSee('Aanwezig bij welke teammomenten/pickups?')
            ->assertDontSee('Afwezigheid vooraf gemeld en alternatief?');
    }

    foreach (completeCheckinFormFor($player) as $field => $value) {
        $component->set($field, $value);
    }

    $component
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('saved', true)
        ->assertSee('Bedankt, je check-in is opgeslagen.');

    $checkin = $player->checkins()->firstOrFail();

    expect($checkin->submitted_at)->not->toBeNull()
        ->and($checkin->strength_sessions)->toBe($player->isMuscleGain() ? 3 : 2)
        ->and($checkin->conditioning_sessions)->toBe($player->isMuscleGain() ? 1 : 2)
        ->and($checkin->mobility_sessions)->toBe(3)
        ->and($checkin->pickup_monday)->toBeTrue()
        ->and($checkin->had_full_rest_day)->toBeTrue()
        ->and($checkin->calculated_training_load)->toBe(720);

    if ($player->tracksNutrition()) {
        expect($checkin->weight_kg)->not->toBeNull()
            ->and($checkin->kcal_avg)->not->toBeNull()
            ->and($checkin->protein_status)->toBe('yes')
            ->and($checkin->protein_target_days)->toBe(7)
            ->and($checkin->appetite_score)->toBe(7);
    } else {
        expect($checkin->weight_kg)->toBeNull()
            ->and($checkin->kcal_avg)->toBeNull()
            ->and($checkin->protein_status)->toBeNull()
            ->and($checkin->appetite_score)->toBeNull();
    }

    if ($player->isGuardDevelopment()) {
        expect($checkin->handle_sessions)->toBe(3)
            ->and($checkin->handle_minutes)->toBe(90)
            ->and($checkin->handles_worked_on)->toContain('retreat')
            ->and($checkin->pickup_sessions)->toBe(2)
            ->and($checkin->pickup_thursday)->toBeTrue()
            ->and($checkin->conditioning_minutes)->toBe(60)
            ->and($checkin->defence_sessions)->toBe(2)
            ->and($checkin->playbook_calls_learned)->toBe(1)
            ->and($checkin->playbook_focus)->toContain('Horns');
    } else {
        expect($checkin->handle_sessions)->toBeNull()
            ->and($checkin->handle_minutes)->toBeNull()
            ->and($checkin->pickup_sessions)->toBeNull()
            ->and($checkin->conditioning_minutes)->toBeNull()
            ->and($checkin->defence_sessions)->toBeNull()
            ->and($checkin->playbook_calls_learned)->toBeNull()
            ->and($checkin->playbook_focus)->toBeNull();
    }
})->with([
    'conditie' => [Player::Conditioning, 4],
    'bulk' => [Player::MuscleGain, 4],
    'onderhoud' => [Player::Maintenance, 3],
    'guard development' => [Player::GuardDevelopment, 4],
]);

test('guard development checkin vereist guard-specifieke velden', function () {
    $player = playerWithUser(['program_type' => Player::GuardDevelopment]);

    Livewire::actingAs($player->user)
        ->test(Checkin::class)
        ->set('form.strength_sessions', 2)
        ->set('form.conditioning_sessions', 2)
        ->set('form.mobility_sessions', 3)
        ->set('form.total_training_minutes', 120)
        ->set('form.highest_session_rpe', 6)
        ->call('nextStep')
        ->assertSet('step', 1)
        ->assertHasErrors([
            'form.handle_sessions' => 'required',
            'form.handle_minutes' => 'required',
            'form.handles_worked_on' => 'required',
            'form.pickup_sessions' => 'required',
            'form.conditioning_minutes' => 'required',
            'form.defence_sessions' => 'required',
            'form.playbook_calls_learned' => 'required',
            'form.playbook_focus' => 'required',
        ])
        ->assertSet('validationScrollField', 'handle_sessions');
});

test('speler kan weekcheck invullen en aanpassen', function () {
    $this->travelTo(Carbon::parse('2026-06-04 10:00'));

    $player = playerWithUser();

    Livewire::actingAs($player->user)
        ->test(Checkin::class)
        ->call('goToStep', 3)
        ->assertSet('step', 1)
        ->assertHasErrors([
            'form.strength_sessions' => 'required',
            'form.conditioning_sessions' => 'required',
            'form.mobility_sessions' => 'required',
        ])
        ->assertSet('validationScrollField', 'strength_sessions')
        ->call('goToStep', 0)
        ->assertSet('step', 1);

    Livewire::actingAs($player->user)
        ->test(Checkin::class)
        ->set('form.strength_sessions', 1)
        ->set('form.conditioning_sessions', 2)
        ->set('form.mobility_sessions', 3)
        ->set('form.had_full_rest_day', true)
        ->set('form.total_training_minutes', 120)
        ->set('form.highest_session_rpe', 6)
        ->set('form.sleep_avg_hours', 7.5)
        ->set('form.energy_score', 7)
        ->set('form.soreness_score', 4)
        ->call('save')
        ->assertHasErrors(['form.missed_target_reason' => 'required'])
        ->assertSet('step', 3)
        ->assertSet('validationScrollField', 'missed_target_reason')
        ->set('form.missed_target_reason', 'geen tijd')
        ->call('save')
        ->assertSet('saved', true)
        ->assertSee('Bedankt, je check-in is opgeslagen.')
        ->assertSee('Je check-in voor deze week is opgeslagen.')
        ->assertDontSee('Verstuur weekcheck');

    expect($player->checkins()->count())->toBe(1);
    expect($player->checkins()->first()->calculated_training_load)->toBe(720);
    expect($player->checkins()->first()->had_full_rest_day)->toBeTrue();

    Livewire::actingAs($player->user)
        ->test(Checkin::class)
        ->set('form.strength_sessions', 2)
        ->call('save');

    expect($player->checkins()->count())->toBe(1)
        ->and($player->checkins()->first()->strength_sessions)->toBe(2);
});

test('speler kan alleen de huidige weekcheck aanpassen', function () {
    $this->travelTo(Carbon::parse('2026-06-04 10:00'));

    $player = playerWithUser();
    $previousWeek = now()->startOfWeek()->subWeek();

    $oldCheckin = WeeklyCheckin::query()->create([
        'player_id' => $player->id,
        'week_start_date' => $previousWeek->toDateString(),
        'strength_sessions' => 1,
        'conditioning_sessions' => 1,
        'mobility_sessions' => 1,
        'sleep_avg_hours' => 7,
        'energy_score' => 6,
        'soreness_score' => 4,
        'submitted_at' => $previousWeek->copy()->addDays(6),
    ]);

    expect($player->user->can('update', $oldCheckin))->toBeFalse();

    Livewire::actingAs($player->user)
        ->test(Checkin::class)
        ->set('form.strength_sessions', 2)
        ->set('form.conditioning_sessions', 2)
        ->set('form.mobility_sessions', 3)
        ->set('form.had_full_rest_day', true)
        ->set('form.total_training_minutes', 120)
        ->set('form.highest_session_rpe', 6)
        ->set('form.sleep_avg_hours', 7.5)
        ->set('form.energy_score', 8)
        ->set('form.soreness_score', 3)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('saved', true);

    expect($oldCheckin->refresh()->strength_sessions)->toBe(1)
        ->and($player->checkins()->count())->toBe(2)
        ->and($player->checkins()->latest('week_start_date')->first()->week_start_date->isSameDay(now()->startOfWeek()))->toBeTrue();
});

test('speler kan vorige week tot en met woensdag invullen', function () {
    $this->travelTo(Carbon::parse('2026-06-03 10:00'));

    $player = playerWithUser();
    $previousWeek = now()->startOfWeek()->subWeek();

    Livewire::actingAs($player->user)
        ->test(Checkin::class)
        ->assertSet('selectedWeekStartDate', $previousWeek->toDateString())
        ->assertSee('Vorige week')
        ->assertSee($previousWeek->format('d-m-Y'))
        ->set('selectedWeekStartDate', $previousWeek->toDateString())
        ->assertSet('selectedWeekStartDate', $previousWeek->toDateString())
        ->set('form.strength_sessions', 2)
        ->set('form.conditioning_sessions', 2)
        ->set('form.mobility_sessions', 3)
        ->set('form.had_full_rest_day', true)
        ->set('form.total_training_minutes', 120)
        ->set('form.highest_session_rpe', 6)
        ->set('form.sleep_avg_hours', 7.5)
        ->set('form.energy_score', 8)
        ->set('form.soreness_score', 3)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('saved', true)
        ->assertSee('Je check-in voor vorige week is opgeslagen.');

    $checkin = $player->checkins()->firstOrFail();

    expect($checkin->week_start_date->isSameDay($previousWeek))->toBeTrue()
        ->and($checkin->submitted_at)->not->toBeNull();
});

test('speler start op deze week als vorige week al verstuurd is', function () {
    $this->travelTo(Carbon::parse('2026-06-03 10:00'));

    $player = playerWithUser();
    $previousWeek = now()->startOfWeek()->subWeek();

    WeeklyCheckin::query()->create([
        'player_id' => $player->id,
        'week_start_date' => $previousWeek->toDateString(),
        'strength_sessions' => 2,
        'conditioning_sessions' => 2,
        'mobility_sessions' => 3,
        'sleep_avg_hours' => 7,
        'energy_score' => 7,
        'soreness_score' => 3,
        'submitted_at' => now(),
    ]);

    Livewire::actingAs($player->user)
        ->test(Checkin::class)
        ->assertSet('selectedWeekStartDate', now()->startOfWeek()->toDateString())
        ->assertSee('Deze week');
});

test('speler ziet na woensdag dat vorige week gemist is', function () {
    $this->travelTo(Carbon::parse('2026-06-04 10:00'));

    $player = playerWithUser();
    $previousWeek = now()->startOfWeek()->subWeek();

    $this->actingAs($player->user)
        ->get(route('player.home'))
        ->assertOk()
        ->assertSee('Weekcheck gemist')
        ->assertSee($previousWeek->format('d-m-Y'));

    Livewire::actingAs($player->user)
        ->test(Checkin::class)
        ->assertSee('Weekcheck gemist')
        ->assertDontSee('open t/m woensdag')
        ->set('selectedWeekStartDate', $previousWeek->toDateString())
        ->assertHasErrors(['selectedWeekStartDate']);
});

test('verkeerd gekoppelde weekcheck kan veilig naar een andere week worden verplaatst', function () {
    $player = playerWithUser(['name' => 'Luuk van Eijk']);

    $checkin = WeeklyCheckin::query()->create([
        'player_id' => $player->id,
        'week_start_date' => '2026-06-01',
        'strength_sessions' => 2,
        'conditioning_sessions' => 2,
        'mobility_sessions' => 3,
        'sleep_avg_hours' => 7,
        'energy_score' => 7,
        'soreness_score' => 3,
        'submitted_at' => Carbon::parse('2026-06-01 07:00'),
    ]);

    $this->artisan('app:move-weekly-checkin-week', [
        'player' => 'Luuk van Eijk',
        'from_week' => '2026-06-01',
        'to_week' => '2026-05-25',
    ])->assertSuccessful();

    expect($checkin->refresh()->week_start_date->toDateString())->toBe('2026-05-25');
});

test('weekcheck verplaatsen stopt als de doelweek al bestaat', function () {
    $player = playerWithUser(['name' => 'Luuk van Eijk']);

    $source = WeeklyCheckin::query()->create([
        'player_id' => $player->id,
        'week_start_date' => '2026-06-01',
        'strength_sessions' => 2,
        'conditioning_sessions' => 2,
        'mobility_sessions' => 3,
        'sleep_avg_hours' => 7,
        'energy_score' => 7,
        'soreness_score' => 3,
        'submitted_at' => Carbon::parse('2026-06-01 07:00'),
    ]);

    WeeklyCheckin::query()->create([
        'player_id' => $player->id,
        'week_start_date' => '2026-05-25',
        'strength_sessions' => 1,
        'conditioning_sessions' => 1,
        'mobility_sessions' => 1,
        'sleep_avg_hours' => 6,
        'energy_score' => 5,
        'soreness_score' => 5,
        'submitted_at' => Carbon::parse('2026-05-31 20:00'),
    ]);

    $this->artisan('app:move-weekly-checkin-week', [
        'player' => 'Luuk van Eijk',
        'from_week' => '2026-06-01',
        'to_week' => '2026-05-25',
    ])->assertFailed();

    expect($source->refresh()->week_start_date->toDateString())->toBe('2026-06-01');
});

test('speler krijgt duidelijke stapmelding bij ontbrekende checkin velden', function () {
    $player = playerWithUser();

    Livewire::actingAs($player->user)
        ->test(Checkin::class)
        ->call('nextStep')
        ->assertHasErrors([
            'form.strength_sessions' => 'required',
            'form.conditioning_sessions' => 'required',
            'form.mobility_sessions' => 'required',
        ])
        ->assertSet('validationScrollField', 'strength_sessions')
        ->assertSet('validationScrollTick', 1)
        ->assertSee('u22-field-error', false)
        ->assertSee('data-checkin-field="strength_sessions"', false)
        ->assertSet('stepError', 'Kies eerst hoeveel keer je kracht, conditie en mobiliteit/preventie hebt gedaan.');
});

test('checkin formulier bewaart concept lokaal voor refresh', function () {
    $player = playerWithUser();

    $this->actingAs($player->user)->get(route('player.checkin'))
        ->assertOk()
        ->assertSee('u22-checkin-draft:player:'.$player->id, false)
        ->assertSee('recordDraftInput', false)
        ->assertSee('restoreDraft()', false)
        ->assertSee('u22CheckinDraftRestored', false)
        ->assertSee('currentStep !== 1', false)
        ->assertSee('clearDraft()', false);
});

test('spierpijn score krijgt duidelijke licht zwaar betekenis', function () {
    expect(WeeklyCheckin::make(['soreness_score' => 2])->sorenessDisplay())->toBe('2/10 lichte spierpijn')
        ->and(WeeklyCheckin::make(['soreness_score' => 5])->sorenessDisplay())->toBe('5/10 matige spierpijn')
        ->and(WeeklyCheckin::make(['soreness_score' => 8])->sorenessDisplay())->toBe('8/10 zware spierpijn');
});

test('checkboxes zijn optioneel bij volgende stap checkin', function () {
    $player = playerWithUser();

    Livewire::actingAs($player->user)
        ->test(Checkin::class)
        ->set('form.strength_sessions', 2)
        ->set('form.conditioning_sessions', 2)
        ->set('form.mobility_sessions', 3)
        ->set('form.total_training_minutes', 120)
        ->set('form.highest_session_rpe', 6)
        ->call('nextStep')
        ->assertSet('step', 2)
        ->assertHasNoErrors([
            'form.pickup_monday',
            'form.pickup_thursday',
            'form.had_full_rest_day',
        ]);
});

test('speler moet trainingsminuten en hoogste rpe invullen bij conditie of pickup', function () {
    $player = playerWithUser();

    Livewire::actingAs($player->user)
        ->test(Checkin::class)
        ->set('form.strength_sessions', 2)
        ->set('form.conditioning_sessions', 1)
        ->set('form.mobility_sessions', 3)
        ->assertSee('Zwaarste sessie')
        ->call('nextStep')
        ->assertSet('step', 1)
        ->assertHasErrors([
            'form.total_training_minutes' => 'required',
            'form.highest_session_rpe' => 'required',
        ])
        ->assertSet('validationScrollField', 'total_training_minutes')
        ->set('form.total_training_minutes', 90)
        ->set('form.highest_session_rpe', 7)
        ->call('nextStep')
        ->assertHasNoErrors([
            'form.total_training_minutes',
            'form.highest_session_rpe',
        ])
        ->assertSet('step', 2);
});

test('herstel stap toont verplichte velden visueel als ze missen', function () {
    $player = playerWithUser();

    Livewire::actingAs($player->user)
        ->test(Checkin::class)
        ->set('form.strength_sessions', 2)
        ->set('form.conditioning_sessions', 2)
        ->set('form.mobility_sessions', 3)
        ->set('form.total_training_minutes', 120)
        ->set('form.highest_session_rpe', 6)
        ->call('nextStep')
        ->assertSet('step', 2)
        ->call('nextStep')
        ->assertSet('step', 2)
        ->assertHasErrors([
            'form.sleep_avg_hours' => 'required',
            'form.energy_score' => 'required',
            'form.soreness_score' => 'required',
        ])
        ->assertSet('validationScrollField', 'sleep_avg_hours')
        ->assertSee('u22-field-error', false)
        ->assertSet('stepError', 'Vul je slaap in en kies je energie- en spierpijnscore.');
});

test('pijnlocatie foutmelding gebruikt compacte checkin styling', function () {
    $player = playerWithUser();

    Livewire::actingAs($player->user)
        ->test(Checkin::class)
        ->set('form.strength_sessions', 2)
        ->set('form.conditioning_sessions', 2)
        ->set('form.mobility_sessions', 3)
        ->set('form.total_training_minutes', 120)
        ->set('form.highest_session_rpe', 6)
        ->call('nextStep')
        ->set('form.sleep_avg_hours', 7.5)
        ->set('form.energy_score', 7)
        ->set('form.soreness_score', 4)
        ->set('form.pain', true)
        ->call('nextStep')
        ->assertSet('step', 2)
        ->assertHasErrors(['form.pain_location' => 'required'])
        ->assertSet('validationScrollField', 'pain_location')
        ->assertSee('u22-inline-error', false)
        ->assertSee('Vul in waar de pijn zit.');
});

test('stapnavigatie springt naar eerste incomplete stap', function () {
    $player = playerWithUser();

    Livewire::actingAs($player->user)
        ->test(Checkin::class)
        ->set('form.strength_sessions', 2)
        ->set('form.conditioning_sessions', 2)
        ->set('form.mobility_sessions', 3)
        ->set('form.total_training_minutes', 120)
        ->set('form.highest_session_rpe', 6)
        ->call('goToStep', 3)
        ->assertSet('step', 2)
        ->assertHasErrors([
            'form.sleep_avg_hours' => 'required',
            'form.energy_score' => 'required',
            'form.soreness_score' => 'required',
        ])
        ->assertSet('validationScrollField', 'sleep_avg_hours');
});

test('onrealistische checkin waardes worden geweigerd', function () {
    $player = playerWithUser();

    Livewire::actingAs($player->user)
        ->test(Checkin::class)
        ->set('form.sleep_avg_hours', 100)
        ->assertHasErrors(['form.sleep_avg_hours' => 'between'])
        ->set('form.energy_score', 99)
        ->assertHasErrors(['form.energy_score' => 'between'])
        ->set('form.total_training_minutes', 3000)
        ->assertHasErrors(['form.total_training_minutes' => 'between']);
});

test('speler checkin velden worden automatisch als concept opgeslagen', function () {
    $player = playerWithUser();

    $component = Livewire::actingAs($player->user)
        ->test(Checkin::class)
        ->set('form.strength_sessions', 2)
        ->assertSet('autosaved', true);

    expect($component->get('autosavedAt'))->not->toBeNull();

    $checkin = $player->checkins()->firstOrFail();

    expect($checkin->strength_sessions)->toBe(2)
        ->and($checkin->submitted_at)->toBeNull();
});

test('autosave werkt bulk details bij zonder de checkin direct in te dienen', function () {
    $player = playerWithUser(['program_type' => Player::MuscleGain]);
    $player->settings()->update([
        'strength_target_per_week' => 3,
        'conditioning_target_per_week' => 1,
        'mobility_target_per_week' => 3,
        'kcal_minimum' => 3000,
        'protein_target_min' => 120,
        'protein_target_max' => 130,
    ]);

    Livewire::actingAs($player->user)
        ->test(Checkin::class)
        ->set('form.pickup_thursday', true)
        ->set('form.weight_kg', 60.5)
        ->set('form.kcal_avg', 3150)
        ->set('form.protein_status', 'partial')
        ->set('form.protein_avg_grams', 105)
        ->set('form.protein_target_days', 4)
        ->set('form.protein_notes', 'Lunch ging goed, ontbijt mist nog eiwit.')
        ->assertSet('autosaved', true);

    $checkin = $player->checkins()->firstOrFail();

    expect($checkin->pickup_thursday)->toBeTrue()
        ->and((float) $checkin->weight_kg)->toBe(60.5)
        ->and($checkin->kcal_avg)->toBe(3150)
        ->and($checkin->protein_status)->toBe('partial')
        ->and($checkin->protein_avg_grams)->toBe(105)
        ->and($checkin->protein_target_days)->toBe(4)
        ->and($checkin->protein_notes)->toBe('Lunch ging goed, ontbijt mist nog eiwit.')
        ->and($checkin->submitted_at)->toBeNull();
});

test('guard development speler bewaart handles pickups conditie en voeding', function () {
    $player = playerWithUser(['program_type' => Player::GuardDevelopment]);
    $player->settings()->update([
        'handle_sessions_target_per_week' => 3,
        'handle_minutes_target_per_week' => 75,
        'pickup_target_per_week' => 1,
        'conditioning_minutes_target_per_week' => 50,
        'defence_sessions_target_per_week' => 2,
        'playbook_calls_target_per_week' => 1,
        'kcal_minimum' => 2800,
        'protein_target_min' => 120,
        'protein_target_max' => 130,
    ]);

    Livewire::actingAs($player->user)
        ->test(Checkin::class)
        ->set('form.strength_sessions', 2)
        ->set('form.conditioning_sessions', 2)
        ->set('form.mobility_sessions', 3)
        ->set('form.handle_sessions', 3)
        ->set('form.handle_minutes', 90)
        ->set('form.handles_worked_on', 'Pound, cross, between, retreat + re-attack, pressure passing.')
        ->set('form.pickup_sessions', 2)
        ->set('form.pickup_monday', true)
        ->set('form.pickup_thursday', true)
        ->set('form.conditioning_minutes', 60)
        ->set('form.defence_sessions', 2)
        ->set('form.playbook_calls_learned', 1)
        ->set('form.playbook_focus', 'Horns startpositie, optie 1/2 en reset.')
        ->set('form.total_training_minutes', 180)
        ->set('form.highest_session_rpe', 8)
        ->set('form.had_full_rest_day', true)
        ->set('form.sleep_avg_hours', 7.5)
        ->set('form.energy_score', 8)
        ->set('form.soreness_score', 3)
        ->set('form.weight_kg', 70.2)
        ->set('form.kcal_avg', 3100)
        ->set('form.protein_target_days', 7)
        ->set('form.appetite_score', 7)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('saved', true);

    $checkin = $player->checkins()->firstOrFail();

    expect($checkin->handle_sessions)->toBe(3)
        ->and($checkin->handle_minutes)->toBe(90)
        ->and($checkin->handles_worked_on)->toContain('retreat')
        ->and($checkin->pickup_sessions)->toBe(2)
        ->and($checkin->conditioning_minutes)->toBe(60)
        ->and($checkin->defence_sessions)->toBe(2)
        ->and($checkin->playbook_calls_learned)->toBe(1)
        ->and($checkin->playbook_focus)->toContain('Horns')
        ->and($checkin->attendance_notes)->toBeNull()
        ->and($checkin->absence_communication_notes)->toBeNull()
        ->and((float) $checkin->weight_kg)->toBe(70.2)
        ->and($checkin->kcal_avg)->toBe(3100)
        ->and($checkin->protein_status)->toBe('yes')
        ->and($checkin->protein_target_days)->toBe(7);
});

test('anders reden is verplicht en gewicht wordt alleen bij bulk opgeslagen', function () {
    $player = playerWithUser(['program_type' => Player::Maintenance]);

    Livewire::actingAs($player->user)
        ->test(Checkin::class)
        ->set('form.weight_kg', 88)
        ->set('form.strength_sessions', 1)
        ->set('form.conditioning_sessions', 2)
        ->set('form.mobility_sessions', 3)
        ->set('form.total_training_minutes', 120)
        ->set('form.highest_session_rpe', 6)
        ->set('form.sleep_avg_hours', 7.5)
        ->set('form.energy_score', 7)
        ->set('form.soreness_score', 4)
        ->set('form.missed_target_reason', 'anders')
        ->call('save')
        ->assertHasErrors(['form.missed_target_reason_other' => 'required'])
        ->set('form.missed_target_reason_other', 'Werk liep uit')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('saved', true);

    $checkin = $player->checkins()->firstOrFail();

    expect($checkin->weight_kg)->toBeNull()
        ->and($checkin->missed_target_reason)->toBe('anders')
        ->and($checkin->missed_target_reason_other)->toBe('Werk liep uit');
});

test('bulk speler vult eiwitdetails in als eiwitdoel niet volledig is gehaald', function () {
    $player = playerWithUser(['program_type' => Player::MuscleGain]);
    $player->settings()->update([
        'strength_target_per_week' => 3,
        'conditioning_target_per_week' => 1,
        'mobility_target_per_week' => 3,
        'kcal_minimum' => 3000,
        'protein_target_min' => 120,
        'protein_target_max' => 130,
    ]);

    Livewire::actingAs($player->user)
        ->test(Checkin::class)
        ->set('form.weight_kg', 60.4)
        ->set('form.strength_sessions', 3)
        ->set('form.conditioning_sessions', 1)
        ->set('form.mobility_sessions', 3)
        ->set('form.total_training_minutes', 90)
        ->set('form.highest_session_rpe', 6)
        ->set('form.sleep_avg_hours', 7.5)
        ->set('form.energy_score', 7)
        ->set('form.soreness_score', 4)
        ->set('form.kcal_avg', 3200)
        ->set('form.protein_status', 'partial')
        ->set('form.appetite_score', 6)
        ->set('form.missed_target_reason', 'geen tijd')
        ->call('save')
        ->assertHasErrors([
            'form.protein_avg_grams' => 'required',
            'form.protein_target_days' => 'required',
            'form.protein_notes' => 'required',
        ])
        ->set('form.protein_avg_grams', 105)
        ->set('form.protein_target_days', 4)
        ->set('form.protein_notes', 'Ontbijt lukte, avondeten was te laag.')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('saved', true);

    $checkin = $player->checkins()->firstOrFail();

    expect($checkin->protein_status)->toBe('partial')
        ->and($checkin->protein_avg_grams)->toBe(105)
        ->and($checkin->protein_target_days)->toBe(4)
        ->and($checkin->protein_notes)->toBe('Ontbijt lukte, avondeten was te laag.');
});

test('bulk speler bewaart aantal eiwitdagen als doel is gehaald', function () {
    $player = playerWithUser(['program_type' => Player::MuscleGain]);
    $player->settings()->update([
        'strength_target_per_week' => 3,
        'conditioning_target_per_week' => 1,
        'mobility_target_per_week' => 3,
        'kcal_minimum' => 3000,
        'protein_target_min' => 120,
        'protein_target_max' => 130,
    ]);

    Livewire::actingAs($player->user)
        ->test(Checkin::class)
        ->set('form.weight_kg', 60.8)
        ->set('form.strength_sessions', 3)
        ->set('form.conditioning_sessions', 1)
        ->set('form.mobility_sessions', 3)
        ->set('form.pickup_thursday', true)
        ->set('form.total_training_minutes', 90)
        ->set('form.highest_session_rpe', 6)
        ->set('form.sleep_avg_hours', 7.5)
        ->set('form.energy_score', 7)
        ->set('form.soreness_score', 4)
        ->set('form.kcal_avg', 3300)
        ->set('form.protein_target_days', 7)
        ->set('form.protein_avg_grams', 90)
        ->set('form.protein_notes', 'Deze details moeten verdwijnen als het doel gehaald is.')
        ->set('form.appetite_score', 6)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('saved', true);

    $checkin = $player->checkins()->firstOrFail();

    expect($checkin->pickup_thursday)->toBeTrue()
        ->and($checkin->protein_status)->toBe('yes')
        ->and($checkin->protein_target_days)->toBe(7)
        ->and($checkin->protein_avg_grams)->toBeNull()
        ->and($checkin->protein_notes)->toBeNull();
});

test('dashboard markeert pijn als rood', function () {
    $player = playerWithUser(['name' => 'Pijn Speler']);
    WeeklyCheckin::query()->create([
        'player_id' => $player->id,
        'week_start_date' => now()->startOfWeek()->toDateString(),
        'strength_sessions' => 2,
        'conditioning_sessions' => 2,
        'mobility_sessions' => 3,
        'pain' => true,
        'energy_score' => 7,
        'soreness_score' => 4,
        'submitted_at' => now(),
    ]);

    Livewire::actingAs(coachUser())
        ->test(Dashboard::class)
        ->assertSee('Pijn gemeld')
        ->assertSee('Rood');
});

test('bulk-speler met te lage gewichtstoename krijgt kcal-advies', function () {
    $player = playerWithUser(['program_type' => Player::MuscleGain]);
    $player->settings()->update([
        'strength_target_per_week' => 3,
        'conditioning_target_per_week' => 1,
        'kcal_training_day' => 3400,
        'protein_target_min' => 120,
    ]);

    WeeklyCheckin::query()->create(['player_id' => $player->id, 'week_start_date' => now()->startOfWeek()->subWeek()->toDateString(), 'weight_kg' => 60, 'strength_sessions' => 3, 'conditioning_sessions' => 1, 'mobility_sessions' => 3, 'kcal_avg' => 3050, 'submitted_at' => now()]);
    WeeklyCheckin::query()->create(['player_id' => $player->id, 'week_start_date' => now()->startOfWeek()->toDateString(), 'weight_kg' => 60.1, 'strength_sessions' => 3, 'conditioning_sessions' => 1, 'mobility_sessions' => 3, 'kcal_avg' => 3050, 'submitted_at' => now()]);

    expect(app(PlayerAdviceService::class)->evaluate($player)['advice'])->toContain('+250 kcal');
});

test('bulk-speler met twee weken geen gewichtstoename krijgt rood kcal advies', function () {
    $player = playerWithUser(['program_type' => Player::MuscleGain]);
    $player->settings()->update([
        'strength_target_per_week' => 3,
        'conditioning_target_per_week' => 1,
        'kcal_training_day' => 3400,
        'protein_target_min' => 120,
    ]);

    WeeklyCheckin::query()->create(['player_id' => $player->id, 'week_start_date' => now()->startOfWeek()->subWeeks(2)->toDateString(), 'weight_kg' => 60, 'strength_sessions' => 3, 'conditioning_sessions' => 1, 'mobility_sessions' => 3, 'kcal_avg' => 3400, 'submitted_at' => now()]);
    WeeklyCheckin::query()->create(['player_id' => $player->id, 'week_start_date' => now()->startOfWeek()->subWeek()->toDateString(), 'weight_kg' => 60, 'strength_sessions' => 3, 'conditioning_sessions' => 1, 'mobility_sessions' => 3, 'kcal_avg' => 3400, 'submitted_at' => now()]);
    WeeklyCheckin::query()->create(['player_id' => $player->id, 'week_start_date' => now()->startOfWeek()->toDateString(), 'weight_kg' => 60, 'strength_sessions' => 3, 'conditioning_sessions' => 1, 'mobility_sessions' => 3, 'kcal_avg' => 3400, 'submitted_at' => now()]);

    $evaluation = app(PlayerAdviceService::class)->evaluate($player);

    expect($evaluation['status'])->toBe('red')
        ->and($evaluation['reason'])->toBe('2 weken geen gewichtstoename')
        ->and($evaluation['next_action'])->toContain('+250 kcal');
});

test('onderhoudsspeler met te weinig krachttraining krijgt oranje signaal', function () {
    $player = playerWithUser(['program_type' => Player::Maintenance]);
    WeeklyCheckin::query()->create(['player_id' => $player->id, 'week_start_date' => now()->startOfWeek()->toDateString(), 'strength_sessions' => 1, 'conditioning_sessions' => 2, 'mobility_sessions' => 3, 'submitted_at' => now()]);

    $evaluation = app(PlayerAdviceService::class)->evaluate($player);

    expect($evaluation['status'])->toBe('orange')
        ->and($evaluation['advice'])->toContain('Kracht onderhouden');
});

test('speler zonder volledige rustdag krijgt oranje signaal', function () {
    $player = playerWithUser(['program_type' => Player::Maintenance]);
    WeeklyCheckin::query()->create([
        'player_id' => $player->id,
        'week_start_date' => now()->startOfWeek()->toDateString(),
        'strength_sessions' => 2,
        'conditioning_sessions' => 2,
        'mobility_sessions' => 3,
        'had_full_rest_day' => false,
        'sleep_avg_hours' => 7.5,
        'energy_score' => 7,
        'soreness_score' => 4,
        'submitted_at' => now(),
    ]);

    $evaluation = app(PlayerAdviceService::class)->evaluate($player);

    expect($evaluation['status'])->toBe('orange')
        ->and($evaluation['reason'])->toBe('Geen volledige rustdag')
        ->and($evaluation['next_action'])->toBe('Plan deze week minimaal 1 volledige rustdag.');
});

test('coach kan analyse-export bekijken', function () {
    $this->travelTo(Carbon::parse('2026-06-09 10:00'));

    $player = playerWithUser(['name' => 'Export Speler']);
    $withoutSelectedWeek = playerWithUser(['name' => 'Geen Export Speler']);
    $draftOnly = playerWithUser(['name' => 'Concept Export Speler']);

    WeeklyCheckin::query()->create([
        'player_id' => $player->id,
        'week_start_date' => now()->startOfWeek()->subWeeks(2)->toDateString(),
        'strength_sessions' => 1,
        'conditioning_sessions' => 2,
        'mobility_sessions' => 2,
        'sleep_avg_hours' => 7,
        'energy_score' => 6,
        'soreness_score' => 5,
        'submitted_at' => now()->subWeek(),
        'notes' => 'Historische context.',
    ]);

    WeeklyCheckin::query()->create([
        'player_id' => $player->id,
        'week_start_date' => now()->startOfWeek()->subWeek()->toDateString(),
        'strength_sessions' => 2,
        'conditioning_sessions' => 2,
        'mobility_sessions' => 3,
        'had_full_rest_day' => true,
        'sleep_avg_hours' => 8,
        'energy_score' => 8,
        'soreness_score' => 3,
        'submitted_at' => now(),
    ]);

    WeeklyCheckin::query()->create([
        'player_id' => $withoutSelectedWeek->id,
        'week_start_date' => now()->startOfWeek()->subWeeks(2)->toDateString(),
        'strength_sessions' => 2,
        'conditioning_sessions' => 2,
        'mobility_sessions' => 3,
        'submitted_at' => now(),
    ]);

    WeeklyCheckin::query()->create([
        'player_id' => $draftOnly->id,
        'week_start_date' => now()->startOfWeek()->subWeek()->toDateString(),
        'strength_sessions' => 2,
        'conditioning_sessions' => 2,
        'mobility_sessions' => 3,
    ]);

    $this->actingAs(coachUser())->get(route('coach.analysis-export'))
        ->assertOk()
        ->assertSee('Schrijf persoonlijke coachadviezen')
        ->assertSee('Adviesweek 2026-06-01')
        ->assertSee('Export Speler')
        ->assertSee('Historische check-ins in context: 2')
        ->assertSee('Historische context.')
        ->assertDontSee('Geen Export Speler')
        ->assertDontSee('Concept Export Speler')
        ->assertDontSee('Next action');
});

test('coach kan csv export downloaden', function () {
    $this->travelTo(Carbon::parse('2026-06-09 10:00'));

    $player = playerWithUser(['name' => 'Csv Speler']);
    $excluded = playerWithUser(['name' => 'Csv Zonder Week']);

    WeeklyCheckin::query()->create([
        'player_id' => $player->id,
        'week_start_date' => now()->startOfWeek()->subWeek()->toDateString(),
        'strength_sessions' => 2,
        'conditioning_sessions' => 2,
        'mobility_sessions' => 3,
        'sleep_avg_hours' => 8,
        'energy_score' => 8,
        'soreness_score' => 3,
        'submitted_at' => now(),
    ]);

    WeeklyCheckin::query()->create([
        'player_id' => $excluded->id,
        'week_start_date' => now()->startOfWeek()->toDateString(),
        'strength_sessions' => 2,
        'conditioning_sessions' => 2,
        'mobility_sessions' => 3,
        'submitted_at' => now(),
    ]);

    $response = $this->actingAs(coachUser())->get(route('coach.analysis-export.csv', ['week' => '2026-W23']))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');

    expect($response->streamedContent())->toContain('Csv Speler')
        ->not->toContain('Csv Zonder Week')
        ->not->toContain('advice');
});

test('coach dashboard toont actiecentrum met next action en whatsapp', function () {
    $player = playerWithUser(['name' => 'Actie Speler', 'program_type' => Player::Maintenance]);
    WeeklyCheckin::query()->create([
        'player_id' => $player->id,
        'week_start_date' => now()->startOfWeek()->toDateString(),
        'strength_sessions' => 1,
        'conditioning_sessions' => 2,
        'mobility_sessions' => 3,
        'submitted_at' => now(),
    ]);

    Livewire::actingAs(coachUser())
        ->test(Dashboard::class)
        ->assertSee('Week bijsturen')
        ->assertSee('Plan deze week minimaal 2 krachttrainingen.')
        ->assertSee('Kopieer WhatsApp')
        ->assertDontSee('@js(', false);
});

test('coach kan dashboard op een vorige week zetten', function () {
    $this->travelTo(Carbon::parse('2026-06-01 09:00'));

    $player = playerWithUser(['name' => 'Vorige Week Speler', 'program_type' => Player::Maintenance]);

    WeeklyCheckin::query()->create([
        'player_id' => $player->id,
        'week_start_date' => now()->startOfWeek()->subWeek()->toDateString(),
        'strength_sessions' => 2,
        'conditioning_sessions' => 2,
        'mobility_sessions' => 3,
        'had_full_rest_day' => true,
        'sleep_avg_hours' => 8,
        'energy_score' => 7,
        'soreness_score' => 4,
        'submitted_at' => now(),
    ]);

    WeeklyCheckin::query()->create([
        'player_id' => $player->id,
        'week_start_date' => now()->startOfWeek()->toDateString(),
        'strength_sessions' => 1,
        'conditioning_sessions' => 2,
        'mobility_sessions' => 3,
        'submitted_at' => now(),
    ]);

    Livewire::actingAs(coachUser())
        ->test(Dashboard::class)
        ->assertSee('Te weinig krachttraining')
        ->set('week', '2026-W22')
        ->assertSet('week', '2026-W22')
        ->assertSee('Week 22')
        ->assertSee('25-05-2026 t/m 31-05-2026')
        ->assertSee('Op schema')
        ->assertSee('100%')
        ->assertDontSee('Te weinig krachttraining');
});

test('dashboard advies en opvolging gebruiken de geselecteerde week', function () {
    $this->travelTo(Carbon::parse('2026-06-01 09:00'));

    $coach = coachUser();
    $player = playerWithUser(['name' => 'Advies Week Speler', 'program_type' => Player::Maintenance]);

    Livewire::actingAs($coach)
        ->test(Dashboard::class)
        ->set('week', '2026-W22')
        ->call('generateAdvice', $player->id)
        ->call('markFollowedUp', $player->id);

    $advice = CoachNote::query()
        ->whereBelongsTo($player)
        ->where('type', 'advice')
        ->firstOrFail();

    $followUp = CoachNote::query()
        ->whereBelongsTo($player)
        ->where('type', 'training')
        ->firstOrFail();

    expect($advice->week_start_date->toDateString())->toBe('2026-05-25')
        ->and($followUp->week_start_date->toDateString())->toBe('2026-05-25');
});

test('coach kan actie markeren als opgevolgd', function () {
    $coach = coachUser();
    $player = playerWithUser(['name' => 'Opvolg Speler']);

    Livewire::actingAs($coach)
        ->test(Dashboard::class)
        ->call('markFollowedUp', $player->id);

    expect(CoachNote::query()->where('player_id', $player->id)->where('title', 'Actie opgevolgd')->exists())->toBeTrue();
});

test('speler detail toont timeline en bulk dashboard', function () {
    $player = playerWithUser(['name' => 'Bulk Detail', 'program_type' => Player::MuscleGain]);
    $player->settings()->update(['strength_target_per_week' => 3, 'conditioning_target_per_week' => 1, 'kcal_minimum' => 3000]);
    WeeklyCheckin::query()->create([
        'player_id' => $player->id,
        'week_start_date' => now()->startOfWeek()->toDateString(),
        'weight_kg' => 60.2,
        'strength_sessions' => 2,
        'conditioning_sessions' => 1,
        'mobility_sessions' => 3,
        'kcal_avg' => 2900,
        'protein_status' => 'partial',
        'appetite_score' => 6,
        'submitted_at' => now(),
    ]);

    Livewire::actingAs(coachUser())
        ->test(Show::class, ['player' => $player])
        ->assertSee('Bulk-dashboard')
        ->assertSee('17 aug doel')
        ->assertSee('Stretchdoel')
        ->assertSee('Speler-tijdlijn')
        ->assertSee('Kopieer WhatsApp-bericht')
        ->assertDontSee('@js(', false);
});

test('analyse export gebruikt gekozen week en training load historie', function () {
    $this->travelTo(Carbon::parse('2026-06-01 09:00'));

    $player = playerWithUser(['name' => 'Load Export']);
    WeeklyCheckin::query()->create([
        'player_id' => $player->id,
        'week_start_date' => now()->startOfWeek()->subWeek()->toDateString(),
        'strength_sessions' => 1,
        'conditioning_sessions' => 2,
        'mobility_sessions' => 3,
        'total_training_minutes' => 80,
        'highest_session_rpe' => 6,
        'calculated_training_load' => 480,
        'submitted_at' => now(),
    ]);

    WeeklyCheckin::query()->create([
        'player_id' => $player->id,
        'week_start_date' => now()->startOfWeek()->toDateString(),
        'strength_sessions' => 2,
        'conditioning_sessions' => 2,
        'mobility_sessions' => 3,
        'total_training_minutes' => 100,
        'highest_session_rpe' => 7,
        'calculated_training_load' => 700,
        'submitted_at' => now(),
    ]);

    Livewire::actingAs(coachUser())
        ->test(AnalysisExport::class)
        ->set('week', '2026-W22')
        ->assertSee('Adviesweek 2026-05-25')
        ->assertSee('Load Export')
        ->assertSee('Teamdoel')
        ->assertSee('load 480')
        ->assertDontSee('load 700');
});

test('seeders volgen targets uit de spelersversie pdf', function () {
    $this->seed();

    $player = Player::query()->where('name', 'Daan Conditie')->with('settings')->firstOrFail();
    $bulk = Player::query()->where('name', 'Milan Bulk')->with('settings')->firstOrFail();
    $guard = Player::query()->where('name', 'Jos Guard')->with('settings')->firstOrFail();

    expect($player->settings->strength_target_per_week)->toBe(2)
        ->and($player->settings->conditioning_target_per_week)->toBe(2)
        ->and($player->settings->mobility_target_per_week)->toBe(3)
        ->and($bulk->settings->strength_target_per_week)->toBe(3)
        ->and($bulk->settings->conditioning_target_per_week)->toBe(1)
        ->and($bulk->settings->kcal_pickup_day)->toBe(3600)
        ->and($bulk->notes)->toContain('66-68 kg richting 17 augustus')
        ->and($guard->program_type)->toBe(Player::GuardDevelopment)
        ->and($guard->settings->handle_sessions_target_per_week)->toBe(3)
        ->and($guard->settings->handle_minutes_target_per_week)->toBe(75)
        ->and($guard->settings->pickup_target_per_week)->toBe(1)
        ->and($guard->settings->defence_sessions_target_per_week)->toBe(2)
        ->and($guard->settings->playbook_calls_target_per_week)->toBe(1)
        ->and(ProgramTemplate::query()->where('type', Player::Maintenance)->firstOrFail()->goal)->toContain('minimaal 1 volledige rustdag')
        ->and(ProgramTemplate::query()->where('type', Player::MuscleGain)->firstOrFail()->goal)->toContain('3x kracht')
        ->and(ProgramTemplate::query()->where('type', Player::GuardDevelopment)->firstOrFail()->goal)->toContain('handles/passing')
        ->and(ProgramPhase::query()->where('name', 'Fase 0: 11 mei t/m 6 juni - pickup + kracht onderhouden')->exists())->toBeTrue()
        ->and(ExerciseLibraryItem::query()->where('name', 'C4 Repeated sprint + COD')->exists())->toBeTrue()
        ->and(ExerciseLibraryItem::query()->where('name', '8 minuten preventieblok')->exists())->toBeTrue()
        ->and(ExerciseLibraryItem::query()->where('name', 'Spiermassa persoonlijke targets')->exists())->toBeTrue()
        ->and(ExerciseLibraryItem::query()->where('name', 'Pickup voeding')->exists())->toBeTrue();
});
