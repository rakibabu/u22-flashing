<?php

namespace App\Livewire\Coach\Players;

use App\Models\Player;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class CheckinPreview extends Component
{
    use AuthorizesRequests;

    public Player $player;

    public array $form = [
        'weight_kg' => null,
        'strength_sessions' => null,
        'conditioning_sessions' => null,
        'mobility_sessions' => null,
        'pickup_monday' => null,
        'pickup_thursday' => null,
        'had_full_rest_day' => false,
        'sleep_avg_hours' => null,
        'energy_score' => null,
        'soreness_score' => null,
        'pain' => false,
        'pain_location' => null,
        'pain_notes' => null,
        'rpe_highest' => null,
        'total_training_minutes' => null,
        'highest_session_rpe' => null,
        'calculated_training_load' => null,
        'missed_target_reason' => null,
        'missed_target_reason_other' => null,
        'kcal_avg' => null,
        'protein_status' => null,
        'protein_avg_grams' => null,
        'protein_target_days' => null,
        'protein_notes' => null,
        'appetite_score' => null,
        'used_mijn_eetmeter' => null,
        'used_yazio' => null,
        'notes' => null,
    ];

    public int $step = 1;

    public function mount(Player $player): void
    {
        $this->authorize('view', $player);

        $this->player = $player->loadMissing('settings');
    }

    public function nextStep(): void
    {
        $this->step = min($this->step + 1, $this->maxStep());
    }

    public function previousStep(): void
    {
        $this->step = max($this->step - 1, 1);
    }

    public function goToStep(int $step): void
    {
        $this->step = max(1, min($step, $this->maxStep()));
    }

    public function maxStep(): int
    {
        return $this->player->program_type === Player::Maintenance ? 3 : 4;
    }

    public function render(): View
    {
        return view('livewire.coach.players.checkin-preview', [
            'maxStep' => $this->maxStep(),
        ])->layout('layouts.app');
    }
}
