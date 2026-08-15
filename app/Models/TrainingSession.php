<?php

namespace App\Models;

use App\Enums\TrainingStatus;
use Database\Factories\TrainingSessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrainingSession extends Model
{
    /** @use HasFactory<TrainingSessionFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['created_by', 'source_training_session_id', 'title', 'scheduled_at', 'planned_duration_minutes', 'expected_player_count', 'available_baskets', 'theme', 'goals', 'coach_notes', 'player_notes', 'status', 'is_template', 'published_at', 'completed_at'];

    protected function casts(): array
    {
        return ['scheduled_at' => 'datetime', 'published_at' => 'datetime', 'completed_at' => 'datetime', 'status' => TrainingStatus::class, 'is_template' => 'boolean'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_training_session_id');
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(TrainingBlock::class)->orderBy('position');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(TrainingRun::class);
    }

    public function plannedBlockMinutes(): int
    {
        return (int) $this->blocks()->sum('planned_duration_minutes');
    }
}
