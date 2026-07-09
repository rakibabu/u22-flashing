<?php

use App\Http\Middleware\PreventCoachViewerWrites;
use App\Livewire\Coach\Advice\Index as AdviceIndex;
use App\Livewire\Coach\Dashboard as CoachDashboard;
use App\Livewire\Coach\Players\Index as PlayersIndex;
use App\Livewire\Coach\Players\Show as PlayerShow;
use App\Livewire\Coach\Tests\Index as TestsIndex;
use App\Livewire\TeamDocuments\Show as TeamDocumentShow;
use App\Models\CoachNote;
use App\Models\Player;
use App\Models\PlayerProgramSetting;
use App\Models\ProgramTemplate;
use App\Models\TeamDocument;
use App\Models\TeamInvite;
use App\Models\TestResult;
use App\Models\User;
use App\Models\WeeklyCheckin;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

function coachViewerFixture(): User
{
    return User::factory()->coachViewer()->create();
}

function coachViewerPlayerFixture(array $attributes = []): Player
{
    $player = Player::factory()->create([
        'name' => 'Demo Speler',
        'program_type' => Player::Maintenance,
        ...$attributes,
    ]);

    $player->settings()->create(PlayerProgramSetting::defaultsForProgram($player->program_type));

    return $player;
}

/**
 * @param  array<string, mixed>  $updates
 * @param  array<int, string>  $methods
 */
function coachViewerLivewireRequest(User $viewer, string $componentName, array $updates = [], array $methods = []): Request
{
    $request = Request::create('/livewire-test/update', 'POST', [
        'components' => [[
            'snapshot' => json_encode(['memo' => ['name' => $componentName]], JSON_THROW_ON_ERROR),
            'updates' => $updates,
            'calls' => array_map(fn (string $method): array => ['method' => $method], $methods),
        ]],
    ]);
    $request->setUserResolver(fn (): User => $viewer);

    $route = new Route(['POST'], 'livewire-test/update', fn (): null => null);
    $route->name('default-livewire.update');
    $request->setRouteResolver(fn (): Route => $route);

    return $request;
}

test('provisioning maakt een veilig coach viewer account dat met gebruikersnaam kan inloggen', function () {
    $password = 'Demo-Viewer!2026-Sterk';

    $this->artisan('app:provision-coach-viewer', [
        '--password' => $password,
    ])->assertSuccessful();

    $viewer = User::query()->where('email', 'demo@u22-basketball.nl')->firstOrFail();

    expect($viewer->username)->toBe('demo-coach')
        ->and($viewer->isCoachViewer())->toBeTrue()
        ->and($viewer->isCoach())->toBeFalse()
        ->and($viewer->canAccessCoachArea())->toBeTrue()
        ->and($viewer->email_verified_at)->not->toBeNull()
        ->and(Hash::check($password, $viewer->password))->toBeTrue();

    $this->post(route('login.store'), [
        'email' => 'demo-coach',
        'password' => $password,
    ])->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($viewer);
});

test('provisioning overschrijft nooit een bestaand regulier account', function () {
    $coach = User::factory()->coach()->create(['email' => 'demo@u22-basketball.nl']);

    $this->artisan('app:provision-coach-viewer', [
        '--password' => 'Demo-Viewer!2026-Sterk',
    ])->assertFailed();

    expect($coach->fresh()->isCoach())->toBeTrue()
        ->and(User::query()->where('email', 'demo@u22-basketball.nl')->count())->toBe(1);
});

test('opnieuw provisionen trekt oude demo sessies en authenticatiemiddelen in', function () {
    $viewer = User::factory()->coachViewer()->withTwoFactor()->create([
        'email' => 'demo@u22-basketball.nl',
        'username' => 'demo-coach',
        'remember_token' => 'oud-remember-token',
    ]);
    DB::table('passkeys')->insert([
        'user_id' => $viewer->id,
        'name' => 'Oude passkey',
        'credential_id' => 'oude-demo-passkey',
        'credential' => '{}',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('sessions')->insert([
        'id' => 'oude-demo-sessie',
        'user_id' => $viewer->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Pest',
        'payload' => 'demo',
        'last_activity' => now()->timestamp,
    ]);

    $this->artisan('app:provision-coach-viewer', [
        '--password' => 'Nieuw-Demo!Wachtwoord2026',
    ])->assertSuccessful();

    $viewer->refresh();

    expect(User::query()->where('email', $viewer->email)->count())->toBe(1)
        ->and(Hash::check('Nieuw-Demo!Wachtwoord2026', $viewer->password))->toBeTrue()
        ->and($viewer->remember_token)->not->toBe('oud-remember-token')
        ->and($viewer->two_factor_secret)->toBeNull()
        ->and($viewer->two_factor_recovery_codes)->toBeNull()
        ->and($viewer->two_factor_confirmed_at)->toBeNull()
        ->and(DB::table('passkeys')->where('user_id', $viewer->id)->count())->toBe(0)
        ->and(DB::table('sessions')->where('user_id', $viewer->id)->count())->toBe(0);
});

test('coach viewer ziet de volledige coach leesomgeving', function () {
    $viewer = coachViewerFixture();
    $player = coachViewerPlayerFixture();
    $checkin = WeeklyCheckin::query()->create([
        'player_id' => $player->id,
        'week_start_date' => now()->startOfWeek()->toDateString(),
        'strength_sessions' => 2,
        'conditioning_sessions' => 2,
        'mobility_sessions' => 3,
        'submitted_at' => now(),
    ]);
    CoachNote::query()->create([
        'player_id' => $player->id,
        'coach_user_id' => User::factory()->coach()->create()->id,
        'week_start_date' => now()->startOfWeek()->toDateString(),
        'type' => 'advice',
        'title' => 'Demo advies',
        'body' => 'Zichtbare inhoud voor de andere coach.',
        'visible_to_player' => false,
    ]);
    TestResult::query()->create([
        'player_id' => $player->id,
        'test_date' => now()->toDateString(),
        'sprint_20m_seconds' => 3.21,
    ]);
    TeamDocument::ensureDefaults();

    $pages = [
        [route('coach.dashboard'), 'Demo Speler'],
        [route('coach.players.index'), 'Demo Speler'],
        [route('coach.players.show', $player), 'Demo Speler'],
        [route('coach.players.checkin-preview', $player), 'Coach preview'],
        [route('coach.checkins.index'), 'Demo Speler'],
        [route('coach.checkins.show', $checkin), 'Demo Speler'],
        [route('coach.tests.index'), 'Testresultaten'],
        [route('coach.advice.index'), 'Demo advies'],
        [route('coach.documents.show', TeamDocument::Plays), 'Plays'],
        [route('coach.documents.show', TeamDocument::Playbook), 'Playbook'],
        [route('coach.documents.show', TeamDocument::TeamAgreements), 'Team afspraken'],
        [route('coach.analysis-export'), 'Schrijf persoonlijke coachadviezen'],
    ];

    foreach ($pages as [$url, $visibleText]) {
        $this->actingAs($viewer)
            ->get($url)
            ->assertOk()
            ->assertSee($visibleText)
            ->assertSee('Demomodus');
    }

    $this->actingAs($viewer)
        ->get(route('dashboard'))
        ->assertRedirect(route('coach.dashboard', absolute: false));
});

test('coach viewer mag kijken maar nooit spelers beheren', function () {
    $viewer = coachViewerFixture();
    $player = coachViewerPlayerFixture();

    expect($viewer->can('viewAny', Player::class))->toBeTrue()
        ->and($viewer->can('view', $player))->toBeTrue()
        ->and($viewer->can('create', Player::class))->toBeFalse()
        ->and($viewer->can('update', $player))->toBeFalse()
        ->and($viewer->can('delete', $player))->toBeFalse();

    $this->actingAs($viewer)
        ->get(route('coach.players.create'))
        ->assertForbidden();

    $this->actingAs($viewer)
        ->get(route('coach.players.edit', $player))
        ->assertForbidden();

    $this->actingAs($viewer)
        ->get(route('profile.edit'))
        ->assertForbidden();

    $this->actingAs($viewer)
        ->get(route('security.edit'))
        ->assertForbidden();
});

test('coach viewer ziet data en downloads maar geen beheerknoppen', function () {
    Storage::fake('local');
    $viewer = coachViewerFixture();
    $player = coachViewerPlayerFixture();
    $coach = User::factory()->coach()->create();
    CoachNote::query()->create([
        'player_id' => $player->id,
        'coach_user_id' => $coach->id,
        'week_start_date' => now()->startOfWeek()->toDateString(),
        'type' => 'advice',
        'title' => 'Leesbaar advies',
        'body' => 'Alle details blijven zichtbaar.',
        'visible_to_player' => false,
    ]);
    TestResult::query()->create([
        'player_id' => $player->id,
        'test_date' => now()->toDateString(),
        'sprint_20m_seconds' => 3.21,
    ]);
    $template = ProgramTemplate::ensureDefaults()->firstOrFail();
    $template->update(['training_program_pdf_path' => 'program-templates/demo.pdf']);
    Storage::disk('local')->put($template->training_program_pdf_path, '%PDF-1.4 demo');
    TeamDocument::ensureDefaults();
    $document = TeamDocument::findByType(TeamDocument::Plays);
    $document->update([
        'pdf_path' => 'team-documents/plays/demo.pdf',
        'original_filename' => 'plays.pdf',
    ]);
    Storage::disk('local')->put($document->pdf_path, '%PDF-1.4 demo');

    $this->actingAs($viewer)
        ->get(route('coach.dashboard'))
        ->assertOk()
        ->assertSee('Demo Speler')
        ->assertSee('Analyse export')
        ->assertDontSee('Speler toevoegen')
        ->assertDontSee('Genereer advies')
        ->assertDontSee('Markeer opgevolgd')
        ->assertDontSee('Settings');

    $this->actingAs($viewer)
        ->get(route('coach.players.index'))
        ->assertOk()
        ->assertSee('Bekijk PDF')
        ->assertDontSee('PDF uploaden')
        ->assertDontSee('Nieuwe teamlink')
        ->assertDontSee('Nieuwe invite')
        ->assertDontSee('Verwijder');

    $this->actingAs($viewer)
        ->get(route('coach.players.show', $player))
        ->assertOk()
        ->assertSee('Alle details blijven zichtbaar.')
        ->assertDontSee('Zichtbaar maken voor speler')
        ->assertDontSee('Nieuwe invite')
        ->assertDontSee('Verwijder');

    $this->actingAs($viewer)
        ->get(route('coach.advice.index'))
        ->assertOk()
        ->assertSee('Leesbaar advies')
        ->assertSee('Niet zichtbaar voor speler')
        ->assertDontSee('Bewerk')
        ->assertDontSee('Verwijder');

    $this->actingAs($viewer)
        ->get(route('coach.tests.index'))
        ->assertOk()
        ->assertSee('3.21')
        ->assertDontSee('Opslaan');

    $this->actingAs($viewer)
        ->get(route('coach.documents.show', TeamDocument::Plays))
        ->assertOk()
        ->assertSee('Open PDF')
        ->assertDontSee('PDF uploaden');

    $this->actingAs($viewer)
        ->get(route('coach.program-templates.pdf', $template))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    $this->actingAs($viewer)
        ->get(route('coach.documents.pdf', $document))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    $this->actingAs($viewer)
        ->get(route('coach.analysis-export.csv'))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');
});

test('coach viewer mutations worden server side geweigerd zonder data te wijzigen', function () {
    $viewer = coachViewerFixture();
    $player = coachViewerPlayerFixture();
    $coach = User::factory()->coach()->create();
    $note = CoachNote::query()->create([
        'player_id' => $player->id,
        'coach_user_id' => $coach->id,
        'week_start_date' => now()->startOfWeek()->toDateString(),
        'type' => 'advice',
        'title' => 'Ongewijzigd advies',
        'body' => 'Deze tekst blijft staan.',
        'visible_to_player' => false,
    ]);
    TeamDocument::ensureDefaults();

    Livewire::actingAs($viewer)
        ->test(PlayersIndex::class)
        ->call('generateTeamInvite')
        ->assertForbidden();

    Livewire::actingAs($viewer)
        ->test(PlayersIndex::class)
        ->call('createDefaultProgramTemplates')
        ->assertForbidden();

    Livewire::actingAs($viewer)
        ->test(CoachDashboard::class)
        ->call('generateAdvice', $player->id)
        ->assertForbidden();

    Livewire::actingAs($viewer)
        ->test(PlayerShow::class, ['player' => $player])
        ->call('regenerateInvite')
        ->assertForbidden();

    Livewire::actingAs($viewer)
        ->test(AdviceIndex::class)
        ->call('toggleVisible', $note->id)
        ->assertForbidden();

    Livewire::actingAs($viewer)
        ->test(TestsIndex::class)
        ->call('save')
        ->assertForbidden();

    Livewire::actingAs($viewer)
        ->test(TeamDocumentShow::class, ['type' => TeamDocument::Plays])
        ->call('save')
        ->assertForbidden();

    expect(TeamInvite::query()->count())->toBe(0)
        ->and(ProgramTemplate::query()->count())->toBe(0)
        ->and(CoachNote::query()->count())->toBe(1)
        ->and($note->fresh()->visible_to_player)->toBeFalse()
        ->and(TestResult::query()->count())->toBe(0);
});

test('coach viewer kan geen bestanden uploaden of accountbeveiliging wijzigen', function () {
    Storage::fake('local');
    $viewer = coachViewerFixture();
    $template = ProgramTemplate::ensureDefaults()->first();

    $this->actingAs($viewer)
        ->post(route('coach.program-templates.pdf.store', $template), [])
        ->assertForbidden();

    $this->actingAs($viewer)
        ->post(route('livewire.upload-file'), [])
        ->assertForbidden();

    $blockedRequests = [
        ['post', route('password.confirm.store'), ['password' => 'password']],
        ['post', route('two-factor.enable'), []],
        ['delete', route('two-factor.disable'), []],
        ['post', route('two-factor.regenerate-recovery-codes'), []],
        ['post', route('passkey.store'), []],
    ];

    foreach ($blockedRequests as [$method, $url, $payload]) {
        $this->actingAs($viewer)->{$method}($url, $payload)->assertForbidden();
    }

    expect($template->fresh()->training_program_pdf_path)->toBeNull()
        ->and(Storage::disk('local')->allFiles())->toBe([]);
});

test('fortify wachtwoordreset is ook buiten een ingelogde sessie voor coach viewers geblokkeerd', function () {
    $viewer = User::factory()->coachViewer()->create([
        'password' => Hash::make('Oud-Wachtwoord!2026'),
    ]);
    $token = Password::broker()->createToken($viewer);

    $this->post(route('password.update'), [
        'token' => $token,
        'email' => $viewer->email,
        'password' => 'Nieuw-Wachtwoord!2026',
        'password_confirmation' => 'Nieuw-Wachtwoord!2026',
    ])->assertSessionHasErrors('password');

    expect(Hash::check('Oud-Wachtwoord!2026', $viewer->fresh()->password))->toBeTrue();
});

test('reguliere coach behoudt beheerrechten', function () {
    $coach = User::factory()->coach()->create();

    Livewire::actingAs($coach)
        ->test(PlayersIndex::class)
        ->call('generateTeamInvite')
        ->assertSet('teamInviteLink', fn (?string $link): bool => filled($link));

    expect(TeamInvite::query()->count())->toBe(1);
});

test('read only middleware staat alleen expliciete livewire interacties toe', function () {
    $viewer = coachViewerFixture();
    $middleware = app(PreventCoachViewerWrites::class);
    $next = fn (Request $request): Response => response('toegestaan');

    $allowedPropertyResponse = $middleware->handle(
        coachViewerLivewireRequest($viewer, 'coach.dashboard', ['search' => 'Demo'], ['$commit']),
        $next,
    );
    $allowedMethodResponse = $middleware->handle(
        coachViewerLivewireRequest($viewer, 'coach.dashboard', methods: ['previousWeek']),
        $next,
    );
    $allowedPreviewResponse = $middleware->handle(
        coachViewerLivewireRequest($viewer, 'coach.players.checkin-preview', ['form.strength_sessions' => 2]),
        $next,
    );

    expect($allowedPropertyResponse->getStatusCode())->toBe(200)
        ->and($allowedMethodResponse->getStatusCode())->toBe(200)
        ->and($allowedPreviewResponse->getStatusCode())->toBe(200);

    expect(fn () => $middleware->handle(
        coachViewerLivewireRequest($viewer, 'coach.dashboard', methods: ['generateAdvice']),
        $next,
    ))->toThrow(HttpException::class)
        ->and(fn () => $middleware->handle(
            coachViewerLivewireRequest($viewer, 'coach.dashboard', ['unknown' => true]),
            $next,
        ))->toThrow(HttpException::class);
});

test('teamdocument bekijken maakt nooit records aan', function () {
    $viewer = coachViewerFixture();

    expect(TeamDocument::query()->count())->toBe(0);

    $this->actingAs($viewer)
        ->get(route('coach.documents.show', TeamDocument::Plays))
        ->assertOk()
        ->assertSee('Plays')
        ->assertSee('Nog geen PDF beschikbaar');

    expect(TeamDocument::query()->count())->toBe(0);
});

test('coach viewer kan uitloggen', function () {
    $viewer = coachViewerFixture();

    $this->actingAs($viewer)
        ->post(route('logout'))
        ->assertRedirect(route('home'));

    $this->assertGuest();
});
