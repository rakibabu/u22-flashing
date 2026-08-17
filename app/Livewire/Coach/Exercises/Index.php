<?php

namespace App\Livewire\Coach\Exercises;

use App\Actions\Training\SyncExerciseSnapshots;
use App\Enums\ExerciseScope;
use App\Models\ExerciseLibraryItem;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class Index extends Component
{
    use AuthorizesRequests, WithFileUploads;

    public string $search = '';

    public string $category = '';

    public string $formCategory = '';

    public ?int $editingId = null;

    public bool $showForm = false;

    public string $name = '';

    public string $description = '';

    public string $execution = '';

    public string $coachingCues = '';

    public string $scope = 'both';

    public string $objective = '';

    public string $organization = '';

    public int $duration = 10;

    public ?int $minPlayers = null;

    public ?int $maxPlayers = null;

    public ?int $baskets = null;

    public string $intensity = '';

    public string $coachingPoints = '';

    public string $constraints = '';

    public string $tags = '';

    public string $coachNotes = '';

    public string $videoUrl = '';

    public string $externalUrl = '';

    public $media;

    public function mount(): void
    {
        $this->authorize('viewAny', ExerciseLibraryItem::class);
    }

    public function edit(?int $id = null): void
    {
        $this->authorize('create', ExerciseLibraryItem::class);
        $this->resetForm();
        $this->showForm = true;
        if (! $id) {
            return;
        }
        $exercise = ExerciseLibraryItem::query()->findOrFail($id);
        $this->authorize('update', $exercise);
        $this->editingId = $exercise->id;
        $this->name = $exercise->name;
        $this->description = $exercise->description;
        $this->execution = $exercise->execution;
        $this->coachingCues = $exercise->coaching_cues;
        $this->scope = $exercise->scope->value;
        $this->formCategory = $exercise->category;
        $this->objective = $exercise->objective ?? '';
        $this->organization = $exercise->organization ?? '';
        $this->duration = $exercise->default_duration_minutes ?? 10;
        $this->minPlayers = $exercise->min_players;
        $this->maxPlayers = $exercise->max_players;
        $this->baskets = $exercise->baskets_required;
        $this->intensity = $exercise->intensity ?? '';
        $this->coachingPoints = implode("\n", $exercise->coaching_points ?? []);
        $this->constraints = implode("\n", $exercise->constraints ?? []);
        $this->tags = implode(', ', $exercise->tags ?? []);
        $this->coachNotes = $exercise->coach_notes ?? '';
        $this->videoUrl = $exercise->video_url ?? '';
        $this->externalUrl = $exercise->external_url ?? '';
    }

    public function save(SyncExerciseSnapshots $syncExerciseSnapshots): void
    {
        $this->authorize('create', ExerciseLibraryItem::class);
        $data = $this->validate(['name' => ['required', 'string', 'max:255'], 'formCategory' => ['required', 'string', 'max:255'], 'description' => ['required', 'string'], 'execution' => ['required', 'string'], 'coachingCues' => ['nullable', 'string'], 'scope' => ['required', 'in:individual,team,both'], 'objective' => ['nullable', 'string'], 'organization' => ['nullable', 'string'], 'duration' => ['required', 'integer', 'min:1', 'max:360'], 'minPlayers' => ['nullable', 'integer', 'min:1', 'max:99'], 'maxPlayers' => ['nullable', 'integer', 'min:1', 'max:99', 'gte:minPlayers'], 'baskets' => ['nullable', 'integer', 'min:0', 'max:20'], 'intensity' => ['nullable', 'string', 'max:50'], 'videoUrl' => ['nullable', 'url:https', 'max:2048'], 'externalUrl' => ['nullable', 'url:https', 'max:2048'], 'media' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240']]);
        $exercise = $this->editingId ? ExerciseLibraryItem::query()->findOrFail($this->editingId) : new ExerciseLibraryItem(['created_by' => auth()->id()]);
        $this->authorize($this->editingId ? 'update' : 'create', $exercise);
        if ($this->media) {
            if ($exercise->media_path) {
                Storage::disk('local')->delete($exercise->media_path);
            } $data['media_path'] = $this->media->store('exercise-media', 'local');
            $data['media_type'] = $this->media->getMimeType();
        }
        $exercise->fill(['name' => $data['name'], 'category' => $data['formCategory'], 'description' => $data['description'], 'execution' => $data['execution'], 'coaching_cues' => $data['coachingCues'] ?? '', 'scope' => ExerciseScope::from($data['scope']), 'objective' => $data['objective'] ?: null, 'organization' => $data['organization'] ?: null, 'default_duration_minutes' => $data['duration'], 'min_players' => $data['minPlayers'], 'max_players' => $data['maxPlayers'], 'baskets_required' => $data['baskets'], 'intensity' => $data['intensity'] ?: null, 'coaching_points' => $this->lines($this->coachingPoints), 'constraints' => $this->lines($this->constraints), 'tags' => $this->csv($this->tags), 'coach_notes' => $this->coachNotes ?: null, 'video_url' => $data['videoUrl'] ?: null, 'external_url' => $data['externalUrl'] ?: null, ...collect($data)->only(['media_path', 'media_type'])->all()])->save();
        $syncExerciseSnapshots->handle($exercise);
        $this->resetForm();
        $this->search = '';
        $this->showForm = false;
        session()->flash('exercise-saved', 'Oefening opgeslagen.');
    }

    public function archive(int $id): void
    {
        $exercise = ExerciseLibraryItem::query()->findOrFail($id);
        $this->authorize('delete', $exercise);
        $exercise->archive();
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'formCategory', 'description', 'execution', 'coachingCues', 'objective', 'organization', 'minPlayers', 'maxPlayers', 'baskets', 'intensity', 'coachingPoints', 'constraints', 'tags', 'coachNotes', 'videoUrl', 'externalUrl', 'media']);
        $this->scope = 'both';
        $this->duration = 10;
    }

    /** @return array<int, string> */
    private function lines(string $value): array
    {
        return collect(preg_split('/\r?\n/', $value) ?: [])->map(fn ($line) => trim($line))->filter()->values()->all();
    }

    /** @return array<int, string> */
    private function csv(string $value): array
    {
        return collect(explode(',', $value))->map(fn ($tag) => trim($tag))->filter()->values()->all();
    }

    public function render()
    {
        return view('livewire.coach.exercises.index', ['exercises' => ExerciseLibraryItem::active()->when($this->search, fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))->when($this->category, fn ($q) => $q->where('category', $this->category))->orderBy('category')->orderBy('name')->get(), 'categories' => ExerciseLibraryItem::active()->distinct()->orderBy('category')->pluck('category')])->layout('layouts.app');
    }
}
