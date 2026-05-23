<?php

namespace App\Livewire\Coach\Players;

use App\Models\CoachNote;
use App\Models\Invite;
use App\Models\Player;
use App\Services\PlayerAdviceService;
use App\Services\WhatsAppMessageService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class Show extends Component
{
    use AuthorizesRequests;

    public Player $player;

    public string $adviceBody = '';

    public bool $visibleToPlayer = false;

    public ?string $inviteLink = null;

    public function mount(Player $player, PlayerAdviceService $adviceService): void
    {
        $this->authorize('view', $player);
        $this->player = $player;
        $this->adviceBody = $adviceService->evaluate($player)['advice'];
    }

    public function regenerateInvite(): void
    {
        $this->authorize('update', $this->player);
        [, $token] = Invite::createForPlayer($this->player);
        $this->inviteLink = route('invite.show', $token);
        $this->player->refresh();
    }

    public function saveAdvice(): void
    {
        $this->authorize('update', $this->player);

        $this->validate(['adviceBody' => ['required', 'string', 'max:5000']]);

        CoachNote::query()->create([
            'player_id' => $this->player->id,
            'coach_user_id' => auth()->id(),
            'week_start_date' => now()->startOfWeek()->toDateString(),
            'type' => 'advice',
            'title' => 'Coachadvies',
            'body' => $this->adviceBody,
            'visible_to_player' => $this->visibleToPlayer,
            'sent_at' => $this->visibleToPlayer ? now() : null,
        ]);

        $this->dispatch('advice-saved');
    }

    public function render(PlayerAdviceService $adviceService, WhatsAppMessageService $whatsAppMessageService)
    {
        $this->player->load([
            'settings',
            'latestInvite',
            'checkins' => fn ($query) => $query->latest('week_start_date'),
            'coachNotes' => fn ($query) => $query->latest(),
            'testResults' => fn ($query) => $query->latest('test_date'),
        ]);
        $evaluation = $adviceService->evaluate($this->player);

        return view('livewire.coach.players.show', [
            'evaluation' => $evaluation,
            'bulk' => $this->player->isMuscleGain() ? $adviceService->bulkSummary($this->player) : null,
            'timeline' => $adviceService->timelineFor($this->player),
            'whatsAppMessage' => $whatsAppMessageService->forPlayer($this->player, $evaluation),
        ])->layout('layouts.app');
    }
}
