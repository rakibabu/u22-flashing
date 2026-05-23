<?php

namespace App\Livewire\Coach\Players;

use App\Models\Invite;
use App\Models\Player;
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

    public function save()
    {
        $this->authorize('create', Player::class);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'program_type' => ['required', Rule::in([Player::Conditioning, Player::MuscleGain, Player::Maintenance])],
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
        $settings = match ($player->program_type) {
            Player::Conditioning => ['strength_target_per_week' => 2, 'conditioning_target_per_week' => 2, 'mobility_target_per_week' => 3, 'pickup_monday_expected' => true, 'pickup_thursday_expected' => true],
            Player::MuscleGain => ['strength_target_per_week' => 3, 'conditioning_target_per_week' => 1, 'mobility_target_per_week' => 3, 'pickup_monday_expected' => true, 'pickup_thursday_expected' => false, 'kcal_rest_day' => 3200, 'kcal_training_day' => 3400, 'kcal_pickup_day' => 3600, 'kcal_minimum' => 3000, 'protein_target_min' => 120, 'protein_target_max' => 130, 'uses_mijn_eetmeter' => true, 'uses_yazio_backup' => true, 'notes' => 'Spiermassa persoonlijk: 3x kracht, maandagpickup, geen donderdagpickup, 3000 kcal minimum, 3300-3400 kcal gymdag, 3600 kcal pickupdag, 120-130g eiwit.'],
            default => ['strength_target_per_week' => 2, 'conditioning_target_per_week' => 2, 'mobility_target_per_week' => 3, 'pickup_monday_expected' => true, 'pickup_thursday_expected' => true],
        };

        $player->settings()->create($settings);
    }
}
