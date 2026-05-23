<?php

namespace App\Livewire\Coach;

use App\Models\CoachNote;
use App\Models\Player;
use App\Services\PlayerAdviceService;
use App\Services\WhatsAppMessageService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;

class Dashboard extends Component
{
    use AuthorizesRequests, WithPagination;

    public string $search = '';

    public string $program = '';

    public string $status = '';

    public function generateAdvice(int $playerId, PlayerAdviceService $adviceService): void
    {
        $player = Player::query()->findOrFail($playerId);
        $this->authorize('update', $player);

        $evaluation = $adviceService->evaluate($player);

        CoachNote::query()->create([
            'player_id' => $player->id,
            'coach_user_id' => auth()->id(),
            'week_start_date' => now()->startOfWeek()->toDateString(),
            'type' => $evaluation['status'] === 'green' ? 'praise' : 'advice',
            'title' => 'Bijstuuradvies',
            'body' => $evaluation['advice'],
            'visible_to_player' => false,
        ]);

        $this->dispatch('advice-generated');
    }

    public function markFollowedUp(int $playerId, PlayerAdviceService $adviceService): void
    {
        $player = Player::query()->findOrFail($playerId);
        $this->authorize('update', $player);

        $evaluation = $adviceService->evaluate($player);

        CoachNote::query()->updateOrCreate(
            [
                'player_id' => $player->id,
                'week_start_date' => now()->startOfWeek()->toDateString(),
                'type' => 'training',
                'title' => 'Actie opgevolgd',
            ],
            [
                'coach_user_id' => auth()->id(),
                'body' => $evaluation['next_action'],
                'visible_to_player' => false,
                'sent_at' => now(),
            ],
        );

        $this->dispatch('action-followed-up');
    }

    public function render(PlayerAdviceService $adviceService, WhatsAppMessageService $whatsAppMessageService)
    {
        $weekStart = now()->startOfWeek()->toDateString();
        $followedUpPlayerIds = CoachNote::query()
            ->where('type', 'training')
            ->where('title', 'Actie opgevolgd')
            ->whereDate('week_start_date', $weekStart)
            ->pluck('player_id')
            ->all();

        $allRows = Player::query()
            ->with(['settings', 'latestCheckin', 'checkins' => fn ($query) => $query->orderByDesc('week_start_date')->limit(3)])
            ->where('active', true)
            ->when($this->search, fn ($query) => $query->where('name', 'like', '%'.$this->search.'%'))
            ->when($this->program, fn ($query) => $query->where('program_type', $this->program))
            ->orderBy('name')
            ->get()
            ->map(function (Player $player) use ($adviceService, $followedUpPlayerIds, $whatsAppMessageService): array {
                $evaluation = $adviceService->evaluate($player);

                return [
                    'player' => $player,
                    'checkin' => $player->latestCheckin,
                    'whatsapp' => $whatsAppMessageService->forPlayer($player, $evaluation),
                    'followed_up' => in_array($player->id, $followedUpPlayerIds, true),
                    ...$evaluation,
                ];
            })
            ->values();

        $players = $allRows
            ->when($this->status, fn (Collection $rows) => $rows->where('status', $this->status))
            ->values();

        $missingPlayers = Player::query()
            ->where('active', true)
            ->whereDoesntHave('checkins', fn ($query) => $query->whereDate('week_start_date', $weekStart))
            ->orderBy('name')
            ->get();

        return view('livewire.coach.dashboard', [
            'rows' => $players,
            'actionRows' => $allRows->sortBy(fn (array $row): int => match ($row['status']) {
                'red' => 0,
                'orange' => 1,
                default => 2,
            })->values(),
            'bulkRows' => $allRows
                ->filter(fn (array $row): bool => $row['player']->isMuscleGain())
                ->map(fn (array $row): array => $row + ['bulk' => $adviceService->bulkSummary($row['player'])])
                ->values(),
            'missingPlayers' => $missingPlayers,
            'groupReminder' => $missingPlayers->isNotEmpty() ? $whatsAppMessageService->groupCheckinReminder($missingPlayers) : '',
            'activePlayers' => Player::query()->where('active', true)->count(),
            'checkinsThisWeek' => Player::query()->whereHas('checkins', fn ($query) => $query->whereDate('week_start_date', $weekStart))->count(),
            'missingThisWeek' => Player::query()->where('active', true)->whereDoesntHave('checkins', fn ($query) => $query->whereDate('week_start_date', $weekStart))->count(),
            'redSignals' => $players->where('status', 'red')->count(),
            'orangeSignals' => $players->where('status', 'orange')->count(),
            'painSignals' => $players->filter(fn (array $row): bool => (bool) $row['checkin']?->pain)->count(),
            'avgCompliance' => (int) round($players->avg('compliance') ?: 0),
        ])->layout('layouts.app');
    }
}
