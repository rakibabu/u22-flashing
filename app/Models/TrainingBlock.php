<?php

namespace App\Models;

use App\Enums\TrainingBlockType;
use App\Enums\TrainingCoach;
use Database\Factories\TrainingBlockFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingBlock extends Model
{
    /** @use HasFactory<TrainingBlockFactory> */
    use HasFactory;

    protected $fillable = ['training_session_id', 'exercise_library_item_id', 'block_type', 'position', 'title', 'assigned_coach', 'planned_duration_minutes', 'exercise_snapshot', 'coach_notes', 'player_notes', 'grouping_notes', 'transition_notes'];

    protected function casts(): array
    {
        return ['block_type' => TrainingBlockType::class, 'assigned_coach' => TrainingCoach::class, 'exercise_snapshot' => 'array'];
    }

    public function trainingSession(): BelongsTo
    {
        return $this->belongsTo(TrainingSession::class);
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(ExerciseLibraryItem::class, 'exercise_library_item_id')->withTrashed();
    }

    public function runs(): HasMany
    {
        return $this->hasMany(TrainingBlockRun::class);
    }
}
