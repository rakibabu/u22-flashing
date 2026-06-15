<?php

namespace App\Livewire\Coach;

use App\Models\CoachNote;
use App\Models\Player;
use App\Models\WeeklyCheckin;
use App\Services\PlayerAdviceService;
use App\Services\WhatsAppMessageService;
use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;

class Dashboard extends Component
{
    use AuthorizesRequests, WithPagination;

    public string $search = '';

    public string $program = '';

    public string $status = '';

    public string $week = '';

    public function mount(): void
    {
        $this->week = $this->formatWeek(now()->startOfWeek());
    }

    public function previousWeek(): void
    {
        $this->week = $this->formatWeek($this->selectedWeekStart()->subWeek());
    }

    public function nextWeek(): void
    {
        $this->week = $this->formatWeek($this->selectedWeekStart()->addWeek());
    }

    public function currentWeek(): void
    {
        $this->week = $this->formatWeek(now()->startOfWeek());
    }

    public function updatedWeek(): void
    {
        $this->week = $this->formatWeek($this->selectedWeekStart());
    }

    public function generateAdvice(int $playerId, PlayerAdviceService $adviceService): void
    {
        $player = Player::query()->findOrFail($playerId);
        $this->authorize('update', $player);

        $weekStart = $this->selectedWeekStart();
        $evaluation = $adviceService->evaluate($player, weekStartDate: $weekStart);

        CoachNote::query()->create([
            'player_id' => $player->id,
            'coach_user_id' => auth()->id(),
            'week_start_date' => $weekStart->toDateString(),
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

        $weekStart = $this->selectedWeekStart();
        $evaluation = $adviceService->evaluate($player, weekStartDate: $weekStart);

        CoachNote::query()->updateOrCreate(
            [
                'player_id' => $player->id,
                'week_start_date' => $weekStart->toDateString(),
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

    public function render(PlayerAdviceService $adviceService, WhatsAppMessageService $whatsAppMessageService): View
    {
        $weekStart = $this->selectedWeekStart();
        $weekStartDate = $weekStart->toDateString();
        $currentWeekStart = now()->startOfWeek();
        $isCurrentWeek = $weekStart->isSameDay($currentWeekStart);

        $followedUpPlayerIds = CoachNote::query()
            ->where('type', 'training')
            ->where('title', 'Actie opgevolgd')
            ->whereDate('week_start_date', $weekStartDate)
            ->pluck('player_id')
            ->all();

        $allRows = Player::query()
            ->with(['settings', 'checkins' => fn ($query) => $query->whereDate('week_start_date', '<=', $weekStartDate)->orderByDesc('week_start_date')->limit(4)])
            ->where('active', true)
            ->when($this->search, fn ($query) => $query->where('name', 'like', '%'.$this->search.'%'))
            ->when($this->program, fn ($query) => $query->where('program_type', $this->program))
            ->orderBy('name')
            ->get()
            ->map(function (Player $player) use ($adviceService, $followedUpPlayerIds, $weekStart, $whatsAppMessageService): array {
                $evaluation = $adviceService->evaluate($player, weekStartDate: $weekStart);
                $checkin = $player->checkins->first(fn (WeeklyCheckin $checkin): bool => $checkin->week_start_date->isSameDay($weekStart));

                return [
                    'player' => $player,
                    'checkin' => $checkin,
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
            ->whereDoesntHave('checkins', fn ($query) => $query->whereDate('week_start_date', $weekStartDate))
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
                ->map(fn (array $row): array => $row + ['bulk' => $adviceService->bulkSummary($row['player'], $weekStart)])
                ->values(),
            'guardRows' => $allRows
                ->filter(fn (array $row): bool => $row['player']->isGuardDevelopment())
                ->map(fn (array $row): array => $row + ['guard' => $adviceService->bulkSummary($row['player'], $weekStart)])
                ->values(),
            'missingPlayers' => $missingPlayers,
            'groupReminder' => $missingPlayers->isNotEmpty() ? $whatsAppMessageService->groupCheckinReminder($missingPlayers) : '',
            'activePlayers' => Player::query()->where('active', true)->count(),
            'checkinsThisWeek' => Player::query()->whereHas('checkins', fn ($query) => $query->whereDate('week_start_date', $weekStartDate))->count(),
            'missingThisWeek' => Player::query()->where('active', true)->whereDoesntHave('checkins', fn ($query) => $query->whereDate('week_start_date', $weekStartDate))->count(),
            'redSignals' => $players->where('status', 'red')->count(),
            'orangeSignals' => $players->where('status', 'orange')->count(),
            'painSignals' => $players->filter(fn (array $row): bool => (bool) $row['checkin']?->pain)->count(),
            'avgCompliance' => (int) round($players->avg('compliance') ?: 0),
            'currentWeekValue' => $this->formatWeek($currentWeekStart),
            'isCurrentWeek' => $isCurrentWeek,
            'selectedWeekNumber' => $weekStart->isoWeek(),
            'selectedWeekRange' => $weekStart->format('d-m-Y').' t/m '.$weekStart->copy()->endOfWeek()->format('d-m-Y'),
        ])->layout('layouts.app');
    }

    private function selectedWeekStart(): CarbonInterface
    {
        $week = $this->week;

        if (preg_match('/^(?<year>\d{4})-W(?<week>\d{2})$/', $week, $matches) === 1) {
            $weekNumber = (int) $matches['week'];

            if ($weekNumber >= 1 && $weekNumber <= 53) {
                return $this->clampToCurrentWeek(
                    now()->setISODate((int) $matches['year'], $weekNumber)->startOfWeek()
                );
            }
        }

        try {
            return $this->clampToCurrentWeek(Carbon::parse($week)->startOfWeek());
        } catch (\Throwable) {
            return now()->startOfWeek();
        }
    }

    private function clampToCurrentWeek(CarbonInterface $weekStart): CarbonInterface
    {
        $currentWeekStart = now()->startOfWeek();

        return $weekStart->gt($currentWeekStart) ? $currentWeekStart : $weekStart;
    }

    private function formatWeek(CarbonInterface $weekStart): string
    {
        return $weekStart->copy()->startOfWeek()->format('o-\WW');
    }
}
