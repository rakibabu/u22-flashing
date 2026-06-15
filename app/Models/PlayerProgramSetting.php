<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerProgramSetting extends Model
{
    protected $fillable = [
        'player_id',
        'strength_target_per_week',
        'conditioning_target_per_week',
        'mobility_target_per_week',
        'handle_sessions_target_per_week',
        'handle_minutes_target_per_week',
        'pickup_target_per_week',
        'conditioning_minutes_target_per_week',
        'defence_sessions_target_per_week',
        'playbook_calls_target_per_week',
        'kcal_rest_day',
        'kcal_training_day',
        'kcal_pickup_day',
        'kcal_minimum',
        'protein_target_min',
        'protein_target_max',
        'pickup_monday_expected',
        'pickup_thursday_expected',
        'uses_mijn_eetmeter',
        'uses_yazio_backup',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'pickup_monday_expected' => 'boolean',
            'pickup_thursday_expected' => 'boolean',
            'uses_mijn_eetmeter' => 'boolean',
            'uses_yazio_backup' => 'boolean',
            'handle_sessions_target_per_week' => 'integer',
            'handle_minutes_target_per_week' => 'integer',
            'pickup_target_per_week' => 'integer',
            'conditioning_minutes_target_per_week' => 'integer',
            'defence_sessions_target_per_week' => 'integer',
            'playbook_calls_target_per_week' => 'integer',
        ];
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaultsForProgram(string $programType): array
    {
        $defaults = [
            'strength_target_per_week' => 2,
            'conditioning_target_per_week' => 2,
            'mobility_target_per_week' => 3,
            'handle_sessions_target_per_week' => null,
            'handle_minutes_target_per_week' => null,
            'pickup_target_per_week' => null,
            'conditioning_minutes_target_per_week' => null,
            'defence_sessions_target_per_week' => null,
            'playbook_calls_target_per_week' => null,
            'kcal_rest_day' => null,
            'kcal_training_day' => null,
            'kcal_pickup_day' => null,
            'kcal_minimum' => null,
            'protein_target_min' => null,
            'protein_target_max' => null,
            'pickup_monday_expected' => true,
            'pickup_thursday_expected' => true,
            'uses_mijn_eetmeter' => false,
            'uses_yazio_backup' => false,
            'notes' => null,
        ];

        return match ($programType) {
            Player::Conditioning => $defaults,
            Player::MuscleGain => array_replace($defaults, [
                'strength_target_per_week' => 3,
                'conditioning_target_per_week' => 1,
                'kcal_rest_day' => 3200,
                'kcal_training_day' => 3400,
                'kcal_pickup_day' => 3600,
                'kcal_minimum' => 3000,
                'protein_target_min' => 120,
                'protein_target_max' => 130,
                'pickup_thursday_expected' => false,
                'uses_mijn_eetmeter' => true,
                'uses_yazio_backup' => true,
                'notes' => 'Spiermassa persoonlijk: 3x kracht, maandagpickup, donderdagpickup optioneel als hij meedoet, 3000 kcal minimum, 3300-3400 kcal gymdag, 3600 kcal pickupdag, 120-130g eiwit.',
            ]),
            Player::GuardDevelopment => array_replace($defaults, [
                'handle_sessions_target_per_week' => 3,
                'handle_minutes_target_per_week' => 75,
                'pickup_target_per_week' => 1,
                'conditioning_minutes_target_per_week' => 50,
                'defence_sessions_target_per_week' => 2,
                'playbook_calls_target_per_week' => 1,
                'kcal_rest_day' => 2800,
                'kcal_training_day' => 3200,
                'kcal_pickup_day' => 3400,
                'kcal_minimum' => 2800,
                'protein_target_min' => 120,
                'protein_target_max' => 130,
                'uses_mijn_eetmeter' => true,
                'uses_yazio_backup' => true,
                'notes' => 'Guard development: structurele aanwezigheid, 3x handles/passing, 75+ handle-minuten, 2x kracht, 2x conditie/pickup, 2x defence first-step, 1 call/play per week en lean bulk-light met gewicht, kcal en eiwit.',
            ]),
            default => $defaults,
        };
    }
}
