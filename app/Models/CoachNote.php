<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoachNote extends Model
{
    protected $fillable = [
        'player_id',
        'coach_user_id',
        'week_start_date',
        'type',
        'title',
        'body',
        'visible_to_player',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'week_start_date' => 'date',
            'visible_to_player' => 'boolean',
            'sent_at' => 'datetime',
        ];
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coach_user_id');
    }
}
