<?php

namespace App\Livewire\Coach\Tests;

use App\Models\Player;
use App\Models\TestResult;
use Livewire\Component;

class Index extends Component
{
    public array $form = ['player_id' => null, 'test_date' => null, 'body_weight_kg' => null, 'sprint_20m_seconds' => null, 'five_min_run_meters' => null, 'notes' => null];

    public function save(): void
    {
        abort_unless(auth()->user()->isCoach(), 403);

        $validated = $this->validate([
            'form.player_id' => ['required', 'exists:players,id'],
            'form.test_date' => ['required', 'date'],
            'form.body_weight_kg' => ['nullable', 'numeric', 'between:40,160'],
            'form.sprint_20m_seconds' => ['nullable', 'numeric', 'between:2,10'],
            'form.five_min_run_meters' => ['nullable', 'integer', 'between:500,2500'],
            'form.notes' => ['nullable', 'string', 'max:2000'],
        ])['form'];

        TestResult::query()->create($validated);
        $this->reset('form');
    }

    public function render()
    {
        return view('livewire.coach.tests.index', [
            'players' => Player::query()->orderBy('name')->get(),
            'results' => TestResult::query()->with('player')->latest('test_date')->get(),
        ])->layout('layouts.app');
    }
}
