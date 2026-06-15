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
}
