<?php

namespace App\Livewire\Coach\Advice;

use App\Models\CoachNote;
use Livewire\Component;

class Index extends Component
{
    public function toggleVisible(int $noteId): void
    {
        $note = CoachNote::query()->findOrFail($noteId);
        abort_unless(auth()->user()->isCoach(), 403);

        $note->update([
            'visible_to_player' => ! $note->visible_to_player,
            'sent_at' => ! $note->visible_to_player ? now() : null,
        ]);
    }

    public function render()
    {
        return view('livewire.coach.advice.index', [
            'notes' => CoachNote::query()->with('player')->latest()->get(),
        ])->layout('layouts.app');
    }
}
