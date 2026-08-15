<?php

namespace App\Livewire\Coach\Trainings;

use App\Actions\Training\CreateExerciseSnapshot;
use App\Actions\Training\ReorderTrainingBlocks;
use App\Enums\TrainingBlockType;
use App\Enums\TrainingCoach;
use App\Enums\TrainingStatus;
use App\Models\ExerciseLibraryItem;
use App\Models\TrainingSession;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class Builder extends Component
{
    use AuthorizesRequests;

    public TrainingSession $training;

    public string $title = '';

    public string $scheduledAt = '';

    public int $plannedDuration = 120;

    public ?int $expectedPlayers = null;

    public ?int $availableBaskets = null;

    public string $theme = '';

    public string $goals = '';

    public string $coachNotes = '';

    public string $search = '';

    public string $exerciseCategory = '';

    public function mount(?TrainingSession $training = null): void
    {
        $this->authorize($training ? 'update' : 'create', $training ?? TrainingSession::class);
        $this->training = $training ?? new TrainingSession;
        if ($training?->exists) {
            $this->title = $training->title;
            $this->scheduledAt = $training->scheduled_at?->format('Y-m-d\\TH:i') ?? '';
            $this->plannedDuration = $training->planned_duration_minutes;
            $this->expectedPlayers = $training->expected_player_count;
            $this->availableBaskets = $training->available_baskets;
            $this->theme = $training->theme ?? '';
            $this->goals = $training->goals ?? '';
            $this->coachNotes = $training->coach_notes ?? '';
        }
    }

    public function save(bool $publish = false): void
    {
        $this->authorize($this->training->exists ? 'update' : 'create', $this->training->exists ? $this->training : TrainingSession::class);
        $data = $this->validate(['title' => ['required', 'string', 'max:255'], 'scheduledAt' => ['nullable', 'date'], 'plannedDuration' => ['required', 'integer', 'min:1', 'max:600'], 'expectedPlayers' => ['nullable', 'integer', 'min:1', 'max:99'], 'availableBaskets' => ['nullable', 'integer', 'min:0', 'max:20'], 'theme' => ['nullable', 'string', 'max:255'], 'goals' => ['nullable', 'string'], 'coachNotes' => ['nullable', 'string']]);
        $this->training->fill(['created_by' => $this->training->created_by ?: auth()->id(), 'title' => $data['title'], 'scheduled_at' => $data['scheduledAt'] ?: null, 'planned_duration_minutes' => $data['plannedDuration'], 'expected_player_count' => $data['expectedPlayers'], 'available_baskets' => $data['availableBaskets'], 'theme' => $data['theme'] ?: null, 'goals' => $data['goals'] ?: null, 'coach_notes' => $data['coachNotes'] ?: null, 'status' => $publish ? TrainingStatus::Published : ($this->training->status ?? TrainingStatus::Draft), 'published_at' => $publish ? now() : $this->training->published_at]);
        $this->training->save();
        $this->redirectRoute('coach.trainings.edit', $this->training, navigate: true);
    }

    public function addExercise(int $exerciseId, CreateExerciseSnapshot $snapshot): void
    {
        $this->authorize('update', $this->training);
        $exercise = ExerciseLibraryItem::active()->findOrFail($exerciseId);
        $this->authorize('view', $exercise);
        $this->training->blocks()->create(['exercise_library_item_id' => $exercise->id, 'block_type' => TrainingBlockType::Exercise, 'position' => $this->nextPosition(), 'title' => $exercise->name, 'assigned_coach' => $exercise->default_coach ?? TrainingCoach::Raki, 'planned_duration_minutes' => $exercise->default_duration_minutes ?? 10, 'exercise_snapshot' => $snapshot->handle($exercise)]);
    }

    public function addText(): void
    {
        $this->authorize('update', $this->training);
        $this->training->blocks()->create(['block_type' => TrainingBlockType::Text, 'position' => $this->nextPosition(), 'title' => 'Nieuw trainingsblok', 'assigned_coach' => TrainingCoach::Raki, 'planned_duration_minutes' => 5]);
    }

    public function updateBlock(int $blockId, string $field, mixed $value): void
    {
        $this->authorize('update', $this->training);
        abort_unless(in_array($field, ['title', 'assigned_coach', 'planned_duration_minutes', 'coach_notes', 'transition_notes'], true), 422);
        $block = $this->training->blocks()->findOrFail($blockId);
        if ($field === 'planned_duration_minutes') {
            abort_unless(is_numeric($value) && (int) $value > 0 && (int) $value <= 360, 422);
            $value = (int) $value;
        }
        if ($field === 'assigned_coach') {
            $value = TrainingCoach::from($value);
        }
        $block->update([$field => $value]);
    }

    public function removeBlock(int $blockId): void
    {
        $this->authorize('update', $this->training);
        $this->training->blocks()->findOrFail($blockId)->delete();
        $this->reorder($this->training->blocks()->pluck('id')->all());
    }

    public function move(int $blockId, string $direction): void
    {
        $ids = $this->training->blocks()->pluck('id')->all();
        $index = array_search($blockId, $ids, true);
        if ($index === false) {
            return;
        } $target = $direction === 'up' ? $index - 1 : $index + 1;
        if (! isset($ids[$target])) {
            return;
        } [$ids[$index], $ids[$target]] = [$ids[$target], $ids[$index]];
        $this->reorder($ids);
    }

    /** @param array<int, int> $ids */
    public function reorder(array $ids): void
    {
        $this->authorize('update', $this->training);
        app(ReorderTrainingBlocks::class)->handle($this->training, $ids);
    }

    private function nextPosition(): int
    {
        return ((int) $this->training->blocks()->max('position')) + 1;
    }

    public function render()
    {
        $blocks = $this->training->exists ? $this->training->blocks()->get() : collect();

        return view('livewire.coach.trainings.builder', ['blocks' => $blocks, 'exercises' => ExerciseLibraryItem::active()->whereIn('scope', ['team', 'both'])->when($this->search, fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))->when($this->exerciseCategory, fn ($q) => $q->where('category', $this->exerciseCategory))->orderBy('name')->limit(30)->get(), 'categories' => ExerciseLibraryItem::active()->whereIn('scope', ['team', 'both'])->distinct()->orderBy('category')->pluck('category'), 'filledMinutes' => $blocks->sum('planned_duration_minutes')])->layout('layouts.app');
    }
}
