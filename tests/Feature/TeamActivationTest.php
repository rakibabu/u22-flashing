<?php

use App\Livewire\Coach\Players\Index as PlayersIndex;
use App\Livewire\Public\TeamActivation;
use App\Models\Invite;
use App\Models\Player;
use App\Models\TeamInvite;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Livewire;

function teamActivationCoach(): User
{
    return User::factory()->coach()->create();
}

function teamActivationPlayer(array $attributes = []): Player
{
    return Player::factory()->create($attributes + [
        'user_id' => null,
        'active' => true,
        'program_type' => Player::Maintenance,
    ]);
}

function teamActivationToken(?User $coach = null, array $attributes = []): array
{
    $token = 'team-token-'.Str::random(24);

    $teamInvite = TeamInvite::query()->create($attributes + [
        'created_by_user_id' => ($coach ?? teamActivationCoach())->id,
        'token_hash' => TeamInvite::hashToken($token),
        'expires_at' => now()->addDays(14),
    ]);

    return [$teamInvite, $token];
}

test('coach kan een teamactivatie-link genereren en kopieren', function () {
    $coach = teamActivationCoach();

    $component = Livewire::actingAs($coach)
        ->test(PlayersIndex::class)
        ->call('generateTeamInvite')
        ->assertSee('Teamactivatie-link')
        ->assertSee('Kopieer link');

    $link = $component->get('teamInviteLink');
    $token = str($link)->afterLast('/')->toString();
    $teamInvite = TeamInvite::query()->firstOrFail();

    expect($link)->toContain('/activate/')
        ->and($teamInvite->created_by_user_id)->toBe($coach->id)
        ->and($teamInvite->token_hash)->toBe(TeamInvite::hashToken($token))
        ->and($teamInvite->token_hash)->not->toBe($token)
        ->and($teamInvite->usable())->toBeTrue();
});

test('coach kan een actieve teamactivatie-link intrekken', function () {
    [$teamInvite] = teamActivationToken(teamActivationCoach());

    Livewire::actingAs(teamActivationCoach())
        ->test(PlayersIndex::class)
        ->call('revokeTeamInvite');

    expect($teamInvite->refresh()->revoked_at)->not->toBeNull()
        ->and($teamInvite->usable())->toBeFalse();
});

test('speler kan met genormaliseerde naam een account maken via teamlink', function () {
    $player = teamActivationPlayer(['name' => 'José  van   Dijk']);
    [$teamInvite, $token] = teamActivationToken();

    Livewire::test(TeamActivation::class, ['token' => $token])
        ->set('name', 'jose van dijk')
        ->call('checkName')
        ->assertSet('step', 2)
        ->assertSet('matchedPlayerName', $player->name)
        ->set('username', 'jose-van-dijk')
        ->set('email', 'jose@example.test')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('activate')
        ->assertRedirect(route('player.home', absolute: false));

    $this->assertAuthenticated();

    $user = User::query()->where('username', 'jose-van-dijk')->firstOrFail();

    expect($player->refresh()->user_id)->toBe($user->id)
        ->and($user->role)->toBe('player')
        ->and($user->email)->toBe('jose@example.test')
        ->and($teamInvite->refresh()->last_used_at)->not->toBeNull();
});

test('onbekende naam maakt geen account', function () {
    teamActivationPlayer(['name' => 'Bestaande Speler']);
    [, $token] = teamActivationToken();

    Livewire::test(TeamActivation::class, ['token' => $token])
        ->set('name', 'Onbekende Speler')
        ->call('checkName')
        ->assertHasErrors('name')
        ->assertSee('We konden je naam niet veilig koppelen');

    expect(User::query()->where('role', 'player')->count())->toBe(0);
});

test('al geclaimde speler kan niet opnieuw via teamlink claimen', function () {
    $user = User::factory()->player()->create();
    teamActivationPlayer(['name' => 'Geclaimde Speler', 'user_id' => $user->id]);
    [, $token] = teamActivationToken();

    Livewire::test(TeamActivation::class, ['token' => $token])
        ->set('name', 'Geclaimde Speler')
        ->call('checkName')
        ->assertHasErrors('name');
});

test('verlopen of ingetrokken teamlink werkt niet', function (array $attributes) {
    [, $token] = teamActivationToken(attributes: $attributes);

    $this->get(route('team-invite.show', $token))->assertNotFound();
})->with([
    'verlopen' => [['expires_at' => now()->subMinute()]],
    'ingetrokken' => [['revoked_at' => now()]],
]);

test('dubbele genormaliseerde namen blokkeren teamactivatie', function () {
    teamActivationPlayer(['name' => 'Jan de Vries']);
    teamActivationPlayer(['name' => ' jan   de vries ']);
    [, $token] = teamActivationToken();

    Livewire::test(TeamActivation::class, ['token' => $token])
        ->set('name', 'Jan de Vries')
        ->call('checkName')
        ->assertHasErrors('name')
        ->assertSee('persoonlijke link');
});

test('teamactivatie rate limiting blokkeert herhaald foutief proberen', function () {
    [, $token] = teamActivationToken();
    $key = 'team-activation:name:'.hash('sha256', $token).'|127.0.0.1';
    RateLimiter::clear($key);

    $component = Livewire::test(TeamActivation::class, ['token' => $token])
        ->set('name', 'Niemand');

    foreach (range(1, 8) as $attempt) {
        $component->call('checkName')->assertHasErrors('name');
    }

    $component
        ->call('checkName')
        ->assertHasErrors('name')
        ->assertSee('Te vaak geprobeerd');
});

test('bestaande persoonlijke invite-link blijft eenmalig werken', function () {
    $player = teamActivationPlayer(['name' => 'Persoonlijke Invite']);
    [$invite, $token] = Invite::createForPlayer($player);

    $this->post(route('invite.store', $token), [
        'username' => 'persoonlijke-invite',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('player.home', absolute: false));

    expect($invite->refresh()->used_at)->not->toBeNull()
        ->and($player->refresh()->user_id)->not->toBeNull();

    $this->post(route('logout'));

    $this->get(route('invite.show', $token))->assertNotFound();
});
