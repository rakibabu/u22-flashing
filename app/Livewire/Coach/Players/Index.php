<?php

namespace App\Livewire\Coach\Players;

use App\Models\Invite;
use App\Models\Player;
use App\Models\ProgramTemplate;
use App\Models\TeamInvite;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class Index extends Component
{
    use AuthorizesRequests;

    public string $search = '';

    public array $inviteLinks = [];

    public ?string $teamInviteLink = null;

    public bool $programTemplatesCreated = false;

    public function generateTeamInvite(): void
    {
        TeamInvite::query()
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->update(['revoked_at' => now()]);

        [, $token] = TeamInvite::createForCoach(auth()->user());

        $this->teamInviteLink = route('team-invite.show', $token);
    }

    public function revokeTeamInvite(): void
    {
        TeamInvite::query()
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first()
            ?->update(['revoked_at' => now()]);

        $this->teamInviteLink = null;
    }

    public function regenerateInvite(int $playerId): void
    {
        $player = Player::query()->findOrFail($playerId);
        $this->authorize('update', $player);

        [, $token] = Invite::createForPlayer($player);
        $this->inviteLinks[$player->id] = route('invite.show', $token);
    }

    public function deletePlayer(int $playerId): void
    {
        $player = Player::query()->findOrFail($playerId);
        $this->authorize('delete', $player);

        $player->delete();

        unset($this->inviteLinks[$playerId]);

        $this->dispatch('player-deleted');
    }

    public function createDefaultProgramTemplates(): void
    {
        $this->authorize('viewAny', Player::class);

        ProgramTemplate::ensureDefaults();

        $this->programTemplatesCreated = true;
    }

    public function render()
    {
        $programTemplates = ProgramTemplate::query()->orderBy('sort_order')->get();
        $missingProgramTemplateCount = count(array_diff(
            array_keys(ProgramTemplate::defaultRows()),
            $programTemplates->pluck('type')->all(),
        ));

        return view('livewire.coach.players.index', [
            'programTemplates' => $programTemplates,
            'missingProgramTemplateCount' => $missingProgramTemplateCount,
            'players' => Player::query()
                ->with('latestInvite')
                ->when($this->search, fn ($query) => $query->where('name', 'like', '%'.$this->search.'%'))
                ->orderBy('name')
                ->get(),
            'latestTeamInvite' => TeamInvite::query()->latest()->first(),
        ])->layout('layouts.app');
    }
}
