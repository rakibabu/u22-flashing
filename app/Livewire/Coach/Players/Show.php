<?php

namespace App\Livewire\Coach\Players;

use App\Models\CoachNote;
use App\Models\Invite;
use App\Models\Player;
use App\Services\CoachAdviceMailService;
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

    public ?int $editingNoteId = null;

    public string $editingNoteTitle = '';

    public string $editingNoteBody = '';

    public bool $editingNoteVisibleToPlayer = false;

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

    public function saveAdvice(CoachAdviceMailService $coachAdviceMailService): void
    {
        $this->authorize('update', $this->player);

        $this->validate(['adviceBody' => ['required', 'string', 'max:5000']]);

        $coachNote = CoachNote::query()->create([
            'player_id' => $this->player->id,
            'coach_user_id' => auth()->id(),
            'week_start_date' => now()->startOfWeek()->toDateString(),
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

    private function resetAdviceEditForm(): void
    {
        $this->editingNoteId = null;
        $this->editingNoteTitle = '';
        $this->editingNoteBody = '';
        $this->editingNoteVisibleToPlayer = false;
        $this->resetValidation();
    }
}
