<?php

namespace App\Livewire\Public;

use App\Exceptions\TeamInviteActivationException;
use App\Services\TeamInviteActivationService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class TeamActivation extends Component
{
    public string $token = '';

    public int $step = 1;

    public string $name = '';

    public ?int $playerId = null;

    public ?string $matchedPlayerName = null;

    public string $username = '';

    public ?string $email = null;

    public string $password = '';

    public string $password_confirmation = '';

    public ?string $expiresAtLabel = null;

    public function mount(string $token, TeamInviteActivationService $teamInviteActivationService): void
    {
        $teamInvite = $teamInviteActivationService->findUsableInvite($token);

        abort_unless($teamInvite, 404);

        $this->token = $token;
        $this->expiresAtLabel = $teamInvite->expires_at->format('d-m-Y H:i');
    }

    public function checkName(TeamInviteActivationService $teamInviteActivationService): void
    {
        $this->ensureNotRateLimited('name', 'name');

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
        ], [], [
            'name' => 'naam',
        ]);

        if (! $teamInviteActivationService->findUsableInvite($this->token)) {
            $this->addError('name', 'Deze activatielink is niet meer geldig. Vraag de coach om een nieuwe link.');

            return;
        }

        try {
            $player = $teamInviteActivationService->claimablePlayerForName($this->name);
        } catch (TeamInviteActivationException) {
            $this->addError('name', 'We konden je naam niet veilig koppelen. Controleer je spelling of vraag de coach om een persoonlijke link.');

            return;
        }

        $this->playerId = $player->id;
        $this->matchedPlayerName = $player->name;
        $this->username = Str::slug($player->name);
        $this->step = 2;
        $this->resetErrorBag();
    }

    public function activate(TeamInviteActivationService $teamInviteActivationService)
    {
        $this->ensureNotRateLimited('activate', 'password');

        $validated = $this->validate([
            'playerId' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('users', 'username')],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [], [
            'username' => 'gebruikersnaam',
            'email' => 'e-mail',
            'password' => 'wachtwoord',
        ]);

        try {
            $user = $teamInviteActivationService->activate(
                token: $this->token,
                playerId: (int) $validated['playerId'],
                name: $validated['name'],
                credentials: [
                    'username' => $validated['username'],
                    'email' => $validated['email'] ?? null,
                    'password' => $validated['password'],
                ],
            );
        } catch (TeamInviteActivationException) {
            $this->addError('name', 'Deze activatie kon niet veilig worden afgerond. Vraag de coach om een persoonlijke link.');
            $this->step = 1;

            return null;
        }

        Auth::login($user);

        return redirect()->route('player.home');
    }

    public function startOver(): void
    {
        $this->step = 1;
        $this->playerId = null;
        $this->matchedPlayerName = null;
        $this->username = '';
        $this->email = null;
        $this->password = '';
        $this->password_confirmation = '';
        $this->resetErrorBag();
    }

    public function render(): View
    {
        return view('livewire.public.team-activation')
            ->layout('layouts.auth.simple');
    }

    private function ensureNotRateLimited(string $action, string $errorKey): void
    {
        $key = 'team-activation:'.$action.':'.hash('sha256', $this->token).'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 8)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                $errorKey => "Te vaak geprobeerd. Probeer opnieuw over {$seconds} seconden.",
            ]);
        }

        RateLimiter::hit($key, 60);
    }
}
