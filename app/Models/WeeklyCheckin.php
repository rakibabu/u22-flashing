<?php

namespace App\Models;

use Database\Factories\WeeklyCheckinFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeeklyCheckin extends Model
{
    /** @use HasFactory<WeeklyCheckinFactory> */
    use HasFactory;

    protected $fillable = [
        'player_id',
        'week_start_date',
        'weight_kg',
        'strength_sessions',
        'conditioning_sessions',
        'mobility_sessions',
        'pickup_monday',
        'pickup_thursday',
        'had_full_rest_day',
        'sleep_avg_hours',
        'energy_score',
        'soreness_score',
        'pain',
        'pain_location',
        'pain_notes',
        'rpe_highest',
        'total_training_minutes',
        'highest_session_rpe',
        'calculated_training_load',
        'missed_target_reason',
        'missed_target_reason_other',
        'kcal_avg',
        'protein_status',
        'protein_avg_grams',
        'protein_target_days',
        'protein_notes',
        'appetite_score',
        'used_mijn_eetmeter',
        'used_yazio',
        'notes',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'week_start_date' => 'date',
            'weight_kg' => 'decimal:2',
            'pickup_monday' => 'boolean',
            'pickup_thursday' => 'boolean',
            'had_full_rest_day' => 'boolean',
            'sleep_avg_hours' => 'decimal:2',
            'energy_score' => 'integer',
            'soreness_score' => 'integer',
            'pain' => 'boolean',
            'rpe_highest' => 'integer',
            'total_training_minutes' => 'integer',
            'highest_session_rpe' => 'integer',
            'calculated_training_load' => 'integer',
            'protein_avg_grams' => 'integer',
            'protein_target_days' => 'integer',
            'used_mijn_eetmeter' => 'boolean',
            'used_yazio' => 'boolean',
            'submitted_at' => 'datetime',
        ];
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function sorenessLabel(): ?string
    {
        if ($this->soreness_score === null) {
            return null;
        }

        if ($this->soreness_score <= 3) {
            return 'lichte spierpijn';
        }

        if ($this->soreness_score <= 6) {
            return 'matige spierpijn';
        }

        return 'zware spierpijn';
    }

    public function sorenessDisplay(): string
    {
        if ($this->soreness_score === null) {
            return 'n.v.t.';
        }

        return "{$this->soreness_score}/10 {$this->sorenessLabel()}";
    }
}
