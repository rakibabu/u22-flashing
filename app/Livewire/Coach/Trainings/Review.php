<?php

namespace App\Livewire\Coach\Trainings;

use App\Enums\TrainingAttendanceStatus;
use App\Models\Player;
use App\Models\TrainingSession;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class Review extends Component
{
    use AuthorizesRequests;

    public TrainingSession $training;

    public string $generalNotes = '';

    public string $whatWorked = '';

    public string $whatToChange = '';

    public string $nextAction = '';

    public function mount(TrainingSession $training): void
    {
        $this->authorize('view', $training);
        $this->training = $training;
        $run = $training->runs()->latest()->firstOrFail();
        $this->generalNotes = $run->general_notes ?? '';
        $this->whatWorked = $run->what_worked ?? '';
        $this->whatToChange = $run->what_to_change ?? '';
        $this->nextAction = $run->next_action ?? '';
    }

    public function save(): void
    {
        $this->authorize('update', $this->training);
        $data = $this->validate(['generalNotes' => ['nullable', 'string', 'max:10000'], 'whatWorked' => ['nullable', 'string', 'max:10000'], 'whatToChange' => ['nullable', 'string', 'max:10000'], 'nextAction' => ['nullable', 'string', 'max:10000']]);
        $this->training->runs()->latest()->firstOrFail()->update(['general_notes' => $data['generalNotes'] ?: null, 'what_worked' => $data['whatWorked'] ?: null, 'what_to_change' => $data['whatToChange'] ?: null, 'next_action' => $data['nextAction'] ?: null]);
        session()->flash('review-saved', 'Evaluatie opgeslagen.');
    }

    public function markEveryonePresent(): void
    {
        $this->authorize('update', $this->training);
        $run = $this->training->runs()->latest()->firstOrFail();
        Player::query()->where('active', true)->get()->each(fn (Player $player) => $run->attendances()->updateOrCreate(['player_id' => $player->id], ['status' => TrainingAttendanceStatus::Present]));
    }

    public function setAttendance(int $playerId, string $status): void
    {
        $this->authorize('update', $this->training);
        $status = TrainingAttendanceStatus::from($status);
        $this->training->runs()->latest()->firstOrFail()->attendances()->updateOrCreate(['player_id' => $playerId], ['status' => $status]);
    }

    public function render()
    {
        $run = $this->training->runs()->with(['blockRuns.trainingBlock', 'attendances'])->latest()->firstOrFail();

        return view('livewire.coach.trainings.review', ['run' => $run, 'players' => Player::query()->where('active', true)->orderBy('name')->get(), 'statuses' => TrainingAttendanceStatus::cases()])->layout('layouts.app');
    }
}
