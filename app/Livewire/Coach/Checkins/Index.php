<?php

namespace App\Livewire\Coach\Checkins;

use App\Models\WeeklyCheckin;
use Livewire\Component;

class Index extends Component
{
    public function render()
    {
        return view('livewire.coach.checkins.index', [
            'checkins' => WeeklyCheckin::query()->with('player')->latest('week_start_date')->get(),
        ])->layout('layouts.app');
    }
}
