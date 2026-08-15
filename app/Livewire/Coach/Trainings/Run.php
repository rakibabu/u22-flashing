<?php

namespace App\Livewire\Coach\Trainings;

use App\Actions\Training\StartTrainingRun;
use App\Enums\TrainingBlockRunStatus;
use App\Enums\TrainingRunStatus;
use App\Enums\TrainingStatus;
use App\Models\TrainingBlock;
use App\Models\TrainingBlockRun;
use App\Models\TrainingRun;
use App\Models\TrainingSession;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Run extends Component
{
    use AuthorizesRequests;

    public TrainingSession $training;

    public TrainingRun $run;

    public string $note = '';

    public function mount(TrainingSession $training, StartTrainingRun $start): void
    {
        $this->authorize('update', $training);
        $this->training = $training;
        $this->run = $start->handle($training, auth()->user());
    }

    public function pause(): void
    {
        $this->authorize('update', $this->training);
        if ($this->run->status === TrainingRunStatus::InProgress) {
            $this->run->update(['status' => TrainingRunStatus::Paused, 'paused_at' => now()]);
            $this->dispatchTimerConfig();
        }
    }

    public function resume(): void
    {
        $this->authorize('update', $this->training);
        if ($this->run->status === TrainingRunStatus::Paused) {
            $this->run->update(['status' => TrainingRunStatus::InProgress, 'total_paused_seconds' => $this->run->total_paused_seconds + $this->run->paused_at->diffInSeconds(now()), 'paused_at' => null]);
            $this->dispatchTimerConfig();
        }
    }

    public function addTwoMinutes(): void
    {
        $blockRun = $this->currentBlockRun();
        $blockRun->increment('added_duration_seconds', 120);
        $this->dispatchTimerConfig();
    }

    public function saveNote(): void
    {
        $this->validate(['note' => ['nullable', 'string', 'max:5000']]);
        $this->currentBlockRun()->update(['notes' => $this->note ?: null]);
    }

    public function next(bool $skip = false): void
    {
        $this->advance($skip, true);
    }

    public function previous(): void
    {
        $blocks = $this->training->blocks()->get();
        $index = $blocks->search(fn (TrainingBlock $block) => $block->id === $this->run->current_training_block_id);
        if ($index === false || $index === 0) {
            return;
        } $this->switchTo($blocks[$index - 1]);
    }

    public function finish(): void
    {
        DB::transaction(function (): void {
            $this->currentBlockRun()->update(['status' => TrainingBlockRunStatus::Completed, 'ended_at' => now(), 'actual_duration_seconds' => $this->currentBlockRun()->started_at->diffInSeconds(now())]);
            $this->run->update(['status' => TrainingRunStatus::Completed, 'ended_at' => now(), 'paused_at' => null]);
            $this->training->update(['status' => TrainingStatus::Completed, 'completed_at' => now()]);
        });
        $this->redirectRoute('coach.trainings.review', $this->training, navigate: true);
    }

    private function advance(bool $skip, bool $complete): void
    {
        $blocks = $this->training->blocks()->get();
        $index = $blocks->search(fn (TrainingBlock $block) => $block->id === $this->run->current_training_block_id);
        $current = $this->currentBlockRun();
        $current->update(['status' => $skip ? TrainingBlockRunStatus::Skipped : TrainingBlockRunStatus::Completed, 'ended_at' => now(), 'actual_duration_seconds' => $current->started_at?->diffInSeconds(now()) ?? 0]);
        if (! isset($blocks[$index + 1])) {
            $this->finish();

            return;
        } $this->switchTo($blocks[$index + 1]);
    }

    private function switchTo(TrainingBlock $block): void
    {
        $this->run->update(['current_training_block_id' => $block->id]);
        $blockRun = $this->run->blockRuns()->firstOrCreate(['training_block_id' => $block->id], ['status' => TrainingBlockRunStatus::InProgress, 'started_at' => now()]);
        if ($blockRun->status === TrainingBlockRunStatus::Pending) {
            $blockRun->update(['status' => TrainingBlockRunStatus::InProgress, 'started_at' => now()]);
        }

        $this->note = $blockRun->notes ?? '';
        $this->dispatchTimerConfig();
    }

    private function currentBlockRun(): TrainingBlockRun
    {
        return $this->run->blockRuns()->where('training_block_id', $this->run->current_training_block_id)->firstOrFail();
    }

    private function dispatchTimerConfig(): void
    {
        $block = $this->training->blocks()->findOrFail($this->run->current_training_block_id);
        $blockRun = $this->currentBlockRun();

        $this->dispatch('training-block-changed', timer: [
            'startedAt' => $blockRun->started_at?->toIso8601String(),
            'pausedAt' => $this->run->paused_at?->toIso8601String(),
            'paused' => $this->run->status === TrainingRunStatus::Paused,
            'added' => $blockRun->added_duration_seconds,
            'planned' => $block->planned_duration_minutes * 60,
            'totalStarted' => $this->run->started_at->toIso8601String(),
            'totalPaused' => $this->run->total_paused_seconds,
        ]);
    }

    public function render()
    {
        $this->run->refresh();
        $block = $this->training->blocks()->findOrFail($this->run->current_training_block_id);
        $blocks = $this->training->blocks()->get();
        $index = $blocks->search(fn (TrainingBlock $item) => $item->id === $block->id);
        $this->note = $this->note ?: ($this->currentBlockRun()->notes ?? '');

        return view('livewire.coach.trainings.run', [
            'block' => $block,
            'index' => $index,
            'totalBlocks' => $blocks->count(),
            'nextBlock' => $blocks[$index + 1] ?? null,
            'blockRun' => $this->currentBlockRun(),
            'offlineTraining' => [
                'id' => $this->training->id,
                'title' => $this->training->title,
                'run_id' => $this->run->id,
                'sync_url' => route('coach.training-runs.offline-events', $this->run),
                'csrf_token' => csrf_token(),
                'blocks' => $blocks->map(fn (TrainingBlock $item): array => [
                    'id' => $item->id,
                    'title' => $item->title,
                    'planned_duration_minutes' => $item->planned_duration_minutes,
                    'snapshot' => $item->exercise_snapshot,
                ])->all(),
            ],
        ])->layout('layouts.app');
    }
}
