<?php

namespace App\Livewire\Player;

use App\Models\ExerciseLibraryItem;
use App\Models\ProgramTemplate;
use Livewire\Component;

class Program extends Component
{
    public function render()
    {
        $player = auth()->user()->player()->with('settings')->firstOrFail();

        return view('livewire.player.program', [
            'player' => $player,
            'template' => ProgramTemplate::query()->with('phases')->where('type', $player->program_type)->first(),
            'exercises' => ExerciseLibraryItem::query()->orderBy('sort_order')->get()->groupBy('category'),
        ])->layout('layouts.app');
    }
}
