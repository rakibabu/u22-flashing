<?php

namespace App\Livewire\Coach\Players;

use App\Models\Invite;
use App\Models\Player;
use App\Models\TeamInvite;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class Index extends Component
{
    use AuthorizesRequests;

    public string $search = '';

    public array $inviteLinks = [];

    public ?string $teamInviteLink = null;

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

    public function render()
    {
        return view('livewire.coach.players.index', [
            'players' => Player::query()
                ->with('latestInvite')
                ->when($this->search, fn ($query) => $query->where('name', 'like', '%'.$this->search.'%'))
                ->orderBy('name')
                ->get(),
            'latestTeamInvite' => TeamInvite::query()->latest()->first(),
        ])->layout('layouts.app');
    }
}
