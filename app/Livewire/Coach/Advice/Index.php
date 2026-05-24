<?php

namespace App\Livewire\Coach\Advice;

use App\Models\CoachNote;
use App\Services\CoachAdviceMailService;
use Livewire\Component;

class Index extends Component
{
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

    public function delete(int $noteId): void
    {
        abort_unless(auth()->user()?->isCoach(), 403);

        CoachNote::query()->findOrFail($noteId)->delete();

        $this->dispatch('advice-deleted');
    }

    public function render()
    {
        return view('livewire.coach.advice.index', [
            'notes' => CoachNote::query()->with('player')->latest()->get(),
        ])->layout('layouts.app');
    }
}
