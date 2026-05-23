<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestResult extends Model
{
    protected $fillable = [
        'player_id',
        'test_date',
        'body_weight_kg',
        'sprint_20m_seconds',
        'repeated_sprint_total_seconds',
        'repeated_sprint_dropoff_percent',
        'five_min_run_meters',
        'agility_5_10_5_seconds',
        'yo_yo_score',
        'notes',
    ];

    protected function casts(): array
    {
        return ['test_date' => 'date'];
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
