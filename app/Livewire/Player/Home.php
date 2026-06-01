<?php

namespace App\Livewire\Player;

use Livewire\Component;

class Home extends Component
{
    public function render()
    {
        $currentWeekStart = now()->startOfWeek();
        $previousWeekStart = $currentWeekStart->copy()->subWeek();

        $player = auth()->user()->player()->with([
            'checkins' => fn ($query) => $query->whereIn('week_start_date', [
                $currentWeekStart->toDateString(),
                $previousWeekStart->toDateString(),
            ]),
            'coachNotes' => fn ($query) => $query->where('visible_to_player', true)->latest(),
        ])->firstOrFail();

        $hasPreviousWeekCheckin = $player->checkins->contains(fn ($checkin): bool => $checkin->week_start_date->isSameDay($previousWeekStart) && $checkin->submitted_at !== null);

        return view('livewire.player.home', [
            'player' => $player,
            'hasCheckinThisWeek' => $player->checkins->contains(fn ($checkin): bool => $checkin->week_start_date->isSameDay($currentWeekStart) && $checkin->submitted_at !== null),
            'hasPreviousWeekCheckin' => $hasPreviousWeekCheckin,
            'previousWeekIsOpen' => now()->dayOfWeekIso <= 3,
            'missedPreviousWeekCheckin' => now()->dayOfWeekIso > 3 && ! $hasPreviousWeekCheckin,
            'previousWeekRange' => $previousWeekStart->format('d-m-Y').' t/m '.$previousWeekStart->copy()->endOfWeek()->format('d-m-Y'),
            'latestAdvice' => $player->coachNotes->first(),
        ])->layout('layouts.app');
    }
}
