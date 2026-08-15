<?php

namespace App\Livewire\Coach\Trainings;

use App\Actions\Training\DuplicateTrainingSession;
use App\Models\TrainingSession;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class Index extends Component
{
    use AuthorizesRequests;

    public string $status = '';

    public string $period = '';

    public function mount(): void
    {
        $this->authorize('viewAny', TrainingSession::class);
    }

    public function duplicate(int $id, DuplicateTrainingSession $duplicate): void
    {
        $training = TrainingSession::query()->findOrFail($id);
        $this->authorize('view', $training);
        $copy = $duplicate->handle($training, auth()->user());
        $this->redirectRoute('coach.trainings.edit', $copy, navigate: true);
    }

    public function render()
    {
        return view('livewire.coach.trainings.index', ['trainings' => TrainingSession::query()->withCount('blocks')->when($this->status, fn ($q) => $q->where('status', $this->status))->when($this->period === 'upcoming', fn ($q) => $q->where('scheduled_at', '>=', now())->orderBy('scheduled_at'))->when($this->period === 'completed', fn ($q) => $q->where('status', 'completed')->latest('completed_at'))->unless($this->period, fn ($q) => $q->orderByRaw('scheduled_at is null, scheduled_at'))->get()])->layout('layouts.app');
    }
}
