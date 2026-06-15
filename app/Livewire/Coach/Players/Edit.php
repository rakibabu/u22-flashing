<?php

namespace App\Livewire\Coach\Players;

use App\Models\Player;
use App\Models\PlayerProgramSetting;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Edit extends Component
{
    use AuthorizesRequests;

    public Player $player;

    public array $form = [];

    public function mount(Player $player): void
    {
        $this->authorize('update', $player);
        $this->player = $player;
        $this->form = $player->only(['name', 'program_type', 'age', 'height_cm', 'start_weight_kg', 'target_weight_kg', 'long_term_target_weight_kg', 'notes']);
    }

    public function save(): mixed
    {
        $validated = $this->validate([
            'form.name' => ['required', 'string', 'max:255'],
            'form.program_type' => ['required', Rule::in([Player::Conditioning, Player::MuscleGain, Player::Maintenance, Player::GuardDevelopment])],
            'form.age' => ['nullable', 'integer', 'between:12,40'],
            'form.height_cm' => ['nullable', 'integer', 'between:140,230'],
            'form.start_weight_kg' => ['nullable', 'numeric', 'between:40,160'],
            'form.target_weight_kg' => ['nullable', 'numeric', 'between:40,160'],
            'form.long_term_target_weight_kg' => ['nullable', 'numeric', 'between:40,160'],
            'form.notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $programChanged = $this->player->program_type !== $validated['form']['program_type'];

        DB::transaction(function () use ($programChanged, $validated): void {
            $this->player->update($validated['form']);

            if ($programChanged) {
                $this->player->settings()->updateOrCreate(
                    ['player_id' => $this->player->id],
                    PlayerProgramSetting::defaultsForProgram($this->player->program_type),
                );
            }
        });

        return redirect()->route('coach.players.show', $this->player);
    }

    public function render()
    {
        return view('livewire.coach.players.edit')->layout('layouts.app');
    }
}
