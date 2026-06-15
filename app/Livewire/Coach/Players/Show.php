<?php

namespace App\Livewire\Coach\Players;

use App\Models\CoachNote;
use App\Models\Invite;
use App\Models\Player;
use App\Services\CoachAdviceMailService;
use App\Services\PlayerAdviceService;
use App\Services\WhatsAppMessageService;
use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Carbon;
use Livewire\Component;

class Show extends Component
{
    use AuthorizesRequests;

    public Player $player;

    public string $adviceBody = '';

    public string $adviceWeek = '';

    public bool $visibleToPlayer = false;

    public ?string $inviteLink = null;

    public ?int $editingNoteId = null;

    public string $editingNoteTitle = '';

    public string $editingNoteBody = '';

    public bool $editingNoteVisibleToPlayer = false;

    public function mount(Player $player, PlayerAdviceService $adviceService): void
    {
        $this->authorize('view', $player);
        $this->player = $player;
        $this->adviceWeek = $this->formatWeek($this->defaultAdviceWeek());
        $this->refreshAdviceBody($adviceService);
    }

    public function previousAdviceWeek(): void
    {
        $this->adviceWeek = $this->formatWeek($this->selectedAdviceWeekStart()->subWeek());
        $this->refreshAdviceBody(app(PlayerAdviceService::class));
    }

    public function nextAdviceWeek(): void
    {
        $this->adviceWeek = $this->formatWeek($this->clampToCurrentWeek($this->selectedAdviceWeekStart()->addWeek()));
        $this->refreshAdviceBody(app(PlayerAdviceService::class));
    }

    public function currentAdviceWeek(): void
    {
        $this->adviceWeek = $this->formatWeek(now()->startOfWeek());
        $this->refreshAdviceBody(app(PlayerAdviceService::class));
    }

    public function updatedAdviceWeek(): void
    {
        $this->adviceWeek = $this->formatWeek($this->selectedAdviceWeekStart());
        $this->refreshAdviceBody(app(PlayerAdviceService::class));
    }

    public function regenerateInvite(): void
    {
        $this->authorize('update', $this->player);
        [, $token] = Invite::createForPlayer($this->player);
        $this->inviteLink = route('invite.show', $token);
        $this->player->refresh();
    }

    public function saveAdvice(CoachAdviceMailService $coachAdviceMailService): void
    {
        $this->authorize('update', $this->player);

        $this->validate(['adviceBody' => ['required', 'string', 'max:5000']]);

        $weekStart = $this->selectedAdviceWeekStart();

        $coachNote = CoachNote::query()->create([
            'player_id' => $this->player->id,
            'coach_user_id' => auth()->id(),
            'week_start_date' => $weekStart->toDateString(),
            'type' => 'advice',
            'title' => 'Coachadvies',
            'body' => $this->adviceBody,
            'visible_to_player' => $this->visibleToPlayer,
        ]);

        $coachAdviceMailService->sendWhenVisible($coachNote);

        $this->dispatch('advice-saved');
    }

    public function editAdvice(int $noteId): void
    {
        $this->authorize('update', $this->player);

        $coachNote = $this->player->coachNotes()->findOrFail($noteId);

        $this->editingNoteId = $coachNote->id;
        $this->editingNoteTitle = $coachNote->title;
        $this->editingNoteBody = $coachNote->body;
        $this->editingNoteVisibleToPlayer = $coachNote->visible_to_player;
    }

    public function updateAdvice(CoachAdviceMailService $coachAdviceMailService): void
    {
        $this->authorize('update', $this->player);

        $this->validate([
            'editingNoteId' => ['required', 'integer', 'exists:coach_notes,id'],
            'editingNoteTitle' => ['required', 'string', 'max:255'],
            'editingNoteBody' => ['required', 'string', 'max:5000'],
            'editingNoteVisibleToPlayer' => ['boolean'],
        ]);

        $coachNote = $this->player->coachNotes()->findOrFail($this->editingNoteId);

        $coachNote->update([
            'title' => $this->editingNoteTitle,
            'body' => $this->editingNoteBody,
            'visible_to_player' => $this->editingNoteVisibleToPlayer,
        ]);

        $coachAdviceMailService->sendWhenVisible($coachNote);

        $this->resetAdviceEditForm();
        $this->dispatch('advice-updated');
    }

    public function cancelAdviceEdit(): void
    {
        $this->resetAdviceEditForm();
    }

    public function deleteAdvice(int $noteId): void
    {
        $this->authorize('update', $this->player);

        $this->player->coachNotes()->findOrFail($noteId)->delete();

        if ($this->editingNoteId === $noteId) {
            $this->resetAdviceEditForm();
        }

        $this->dispatch('advice-deleted');
    }

    public function render(PlayerAdviceService $adviceService, WhatsAppMessageService $whatsAppMessageService): View
    {
        $this->player->load([
            'settings',
            'latestInvite',
            'checkins' => fn ($query) => $query->latest('week_start_date'),
            'coachNotes' => fn ($query) => $query->latest(),
            'testResults' => fn ($query) => $query->latest('test_date'),
        ]);
        $weekStart = $this->selectedAdviceWeekStart();
        $advicePlayer = $this->playerForAdviceWeek($weekStart);
        $selectedAdviceCheckin = $advicePlayer->checkins->first(fn ($checkin): bool => $checkin->week_start_date->isSameDay($weekStart));
        $evaluation = $adviceService->evaluate($advicePlayer, $selectedAdviceCheckin, $weekStart);
        $currentWeekStart = now()->startOfWeek();

        return view('livewire.coach.players.show', [
            'evaluation' => $evaluation,
            'bulk' => $this->player->tracksNutrition() ? $adviceService->bulkSummary($advicePlayer, $weekStart) : null,
            'timeline' => $adviceService->timelineFor($this->player),
            'whatsAppMessage' => $whatsAppMessageService->forPlayer($advicePlayer, $evaluation),
            'selectedAdviceCheckin' => $selectedAdviceCheckin,
            'selectedAdviceWeekNumber' => $weekStart->isoWeek(),
            'selectedAdviceWeekRange' => $weekStart->format('d-m-Y').' t/m '.$weekStart->copy()->endOfWeek()->format('d-m-Y'),
            'currentAdviceWeekValue' => $this->formatWeek($currentWeekStart),
            'isCurrentAdviceWeek' => $weekStart->isSameDay($currentWeekStart),
        ])->layout('layouts.app');
    }

    private function refreshAdviceBody(PlayerAdviceService $adviceService): void
    {
        $weekStart = $this->selectedAdviceWeekStart();
        $advicePlayer = $this->playerForAdviceWeek($weekStart);
        $selectedCheckin = $advicePlayer->checkins->first(fn ($checkin): bool => $checkin->week_start_date->isSameDay($weekStart));

        $this->adviceBody = $adviceService->evaluate($advicePlayer, $selectedCheckin, $weekStart)['advice'];
    }

    private function defaultAdviceWeek(): CarbonInterface
    {
        $latestSubmittedCheckin = $this->player->checkins()
            ->whereNotNull('submitted_at')
            ->latest('week_start_date')
            ->first();

        return $latestSubmittedCheckin?->week_start_date ?? now()->startOfWeek();
    }

    private function playerForAdviceWeek(CarbonInterface $weekStart): Player
    {
        $player = $this->player->fresh(['settings']) ?? $this->player;

        $player->setRelation(
            'checkins',
            $player->checkins()
                ->whereDate('week_start_date', '<=', $weekStart->toDateString())
                ->whereNotNull('submitted_at')
                ->latest('week_start_date')
                ->get(),
        );

        return $player;
    }

    private function selectedAdviceWeekStart(): CarbonInterface
    {
        $week = $this->adviceWeek;

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

    private function resetAdviceEditForm(): void
    {
        $this->editingNoteId = null;
        $this->editingNoteTitle = '';
        $this->editingNoteBody = '';
        $this->editingNoteVisibleToPlayer = false;
        $this->resetValidation();
    }
}
