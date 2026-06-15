<?php

namespace App\Livewire\Coach\Checkins;

use App\Models\WeeklyCheckin;
use App\Services\PlayerAdviceService;
use Illuminate\Contracts\View\View;
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

    public function render(PlayerAdviceService $adviceService): View
    {
        $this->weeklyCheckin->load(['player.settings', 'player.checkins']);
        $evaluation = $adviceService->evaluate(
            $this->weeklyCheckin->player,
            $this->weeklyCheckin,
            $this->weeklyCheckin->week_start_date,
        );

        return view('livewire.coach.checkins.show', [
            'checkin' => $this->weeklyCheckin,
            'player' => $this->weeklyCheckin->player,
            'evaluation' => $evaluation,
        ])->layout('layouts.app');
    }
}
