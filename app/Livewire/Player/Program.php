<?php

namespace App\Livewire\Player;

use App\Models\ExerciseLibraryItem;
use App\Models\ProgramTemplate;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class Program extends Component
{
    public function render()
    {
        $player = auth()->user()->player()->with('settings')->firstOrFail();
        $template = ProgramTemplate::query()->with('phases')->where('type', $player->program_type)->first();

        return view('livewire.player.program', [
            'player' => $player,
            'template' => $template,
            'exercises' => ExerciseLibraryItem::query()->orderBy('sort_order')->get()->groupBy('category'),
            'hasTrainingProgramPdf' => $template?->training_program_pdf_path
                && Storage::disk('local')->exists($template->training_program_pdf_path),
        ])->layout('layouts.app');
    }
}
