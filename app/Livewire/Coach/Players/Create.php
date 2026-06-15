<?php

namespace App\Livewire\Coach\Players;

use App\Models\Invite;
use App\Models\Player;
use App\Models\PlayerProgramSetting;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Create extends Component
{
    use AuthorizesRequests;

    public string $name = '';

    public string $program_type = Player::Maintenance;

    public ?int $age = null;

    public ?int $height_cm = null;

    public ?float $start_weight_kg = null;

    public ?float $target_weight_kg = null;

    public ?float $long_term_target_weight_kg = null;

    public ?string $notes = null;

    public function save(): mixed
    {
        $this->authorize('create', Player::class);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'program_type' => ['required', Rule::in([Player::Conditioning, Player::MuscleGain, Player::Maintenance, Player::GuardDevelopment])],
            'age' => ['nullable', 'integer', 'between:12,40'],
            'height_cm' => ['nullable', 'integer', 'between:140,230'],
            'start_weight_kg' => ['nullable', 'numeric', 'between:40,160'],
            'target_weight_kg' => ['nullable', 'numeric', 'between:40,160'],
            'long_term_target_weight_kg' => ['nullable', 'numeric', 'between:40,160'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $player = Player::query()->create($validated + ['active' => true]);
        $this->createDefaultSettings($player);
        [, $token] = Invite::createForPlayer($player);
        session()->flash('invite_link', route('invite.show', $token));

        return redirect()->route('coach.players.show', $player);
    }

    public function render()
    {
        return view('livewire.coach.players.create')->layout('layouts.app');
    }

    private function createDefaultSettings(Player $player): void
    {
        $player->settings()->create(PlayerProgramSetting::defaultsForProgram($player->program_type));
    }
}
