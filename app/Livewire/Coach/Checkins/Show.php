<?php

namespace App\Livewire\Coach\Checkins;

use App\Models\WeeklyCheckin;
use App\Services\PlayerAdviceService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class Show extends Component
{
    use AuthorizesRequests;

    public WeeklyCheckin $weeklyCheckin;

    public function mount(WeeklyCheckin $weeklyCheckin): void
    {
        $this->authorize('view', $weeklyCheckin);
        $this->weeklyCheckin = $weeklyCheckin;
    }

    public function render(PlayerAdviceService $adviceService)
    {
        $this->weeklyCheckin->load(['player.settings', 'player.checkins']);
        $evaluation = $adviceService->evaluate($this->weeklyCheckin->player, $this->weeklyCheckin);

        return view('livewire.coach.checkins.show', [
            'checkin' => $this->weeklyCheckin,
            'player' => $this->weeklyCheckin->player,
            'evaluation' => $evaluation,
        ])->layout('layouts.app');
    }
}
