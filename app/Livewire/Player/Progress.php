<?php

namespace App\Livewire\Player;

use Livewire\Component;

class Progress extends Component
{
    public function render()
    {
        $player = auth()->user()->player()->with(['checkins' => fn ($query) => $query->latest('week_start_date'), 'testResults' => fn ($query) => $query->latest('test_date')])->firstOrFail();

        return view('livewire.player.progress', ['player' => $player])->layout('layouts.app');
    }
}
