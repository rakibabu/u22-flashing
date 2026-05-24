<?php

namespace App\Livewire\Coach\Advice;

use App\Models\CoachNote;
use App\Services\CoachAdviceMailService;
use Livewire\Component;

class Index extends Component
{
    public ?int $editingNoteId = null;

    public string $editingTitle = '';

    public string $editingBody = '';

    public bool $editingVisibleToPlayer = false;

    public function toggleVisible(int $noteId, CoachAdviceMailService $coachAdviceMailService): void
    {
        $note = CoachNote::query()->findOrFail($noteId);
        abort_unless(auth()->user()->isCoach(), 403);

        $visibleToPlayer = ! $note->visible_to_player;

        $note->update([
            'visible_to_player' => $visibleToPlayer,
        ]);

        $coachAdviceMailService->sendWhenVisible($note);
    }

    public function edit(int $noteId): void
    {
        abort_unless(auth()->user()?->isCoach(), 403);

        $note = CoachNote::query()->findOrFail($noteId);

        $this->editingNoteId = $note->id;
        $this->editingTitle = $note->title;
        $this->editingBody = $note->body;
        $this->editingVisibleToPlayer = $note->visible_to_player;
    }

    public function update(CoachAdviceMailService $coachAdviceMailService): void
    {
        abort_unless(auth()->user()?->isCoach(), 403);

        $this->validate([
            'editingNoteId' => ['required', 'integer', 'exists:coach_notes,id'],
            'editingTitle' => ['required', 'string', 'max:255'],
            'editingBody' => ['required', 'string', 'max:5000'],
            'editingVisibleToPlayer' => ['boolean'],
        ]);

        $note = CoachNote::query()->findOrFail($this->editingNoteId);

        $note->update([
            'title' => $this->editingTitle,
            'body' => $this->editingBody,
            'visible_to_player' => $this->editingVisibleToPlayer,
        ]);

        $coachAdviceMailService->sendWhenVisible($note);

        $this->resetEditForm();
        $this->dispatch('advice-updated');
    }

    public function cancelEdit(): void
    {
        $this->resetEditForm();
    }

    public function delete(int $noteId): void
    {
        abort_unless(auth()->user()?->isCoach(), 403);

        CoachNote::query()->findOrFail($noteId)->delete();

        if ($this->editingNoteId === $noteId) {
            $this->resetEditForm();
        }

        $this->dispatch('advice-deleted');
    }

    public function render()
    {
        return view('livewire.coach.advice.index', [
            'notes' => CoachNote::query()->with('player')->latest()->get(),
        ])->layout('layouts.app');
    }

    private function resetEditForm(): void
    {
        $this->editingNoteId = null;
        $this->editingTitle = '';
        $this->editingBody = '';
        $this->editingVisibleToPlayer = false;
        $this->resetValidation();
    }
}
