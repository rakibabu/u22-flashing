<?php

namespace App\Livewire\Player;

use Livewire\Component;

class Advice extends Component
{
    public function render()
    {
        $player = auth()->user()->player()->with(['coachNotes' => fn ($query) => $query->where('visible_to_player', true)->latest()])->firstOrFail();

        return view('livewire.player.advice', ['notes' => $player->coachNotes])->layout('layouts.app');
    }
}
