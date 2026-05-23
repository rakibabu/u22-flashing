<?php

namespace App\Livewire\Player;

use Livewire\Component;

class Home extends Component
{
    public function render()
    {
        $player = auth()->user()->player()->with(['latestCheckin', 'coachNotes' => fn ($query) => $query->where('visible_to_player', true)->latest()])->firstOrFail();

        return view('livewire.player.home', [
            'player' => $player,
            'hasCheckinThisWeek' => $player->latestCheckin?->week_start_date?->isSameDay(now()->startOfWeek()) ?? false,
            'latestAdvice' => $player->coachNotes->first(),
        ])->layout('layouts.app');
    }
}
