<?php

namespace App\Models;

use App\Enums\TrainingBlockRunStatus;
use Database\Factories\TrainingBlockRunFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TrainingBlockRun extends Model
{
    /** @use HasFactory<TrainingBlockRunFactory> */
    use HasFactory;

    protected $fillable = ['uuid', 'training_run_id', 'training_block_id', 'status', 'started_at', 'ended_at', 'actual_duration_seconds', 'added_duration_seconds', 'notes'];

    protected function casts(): array
    {
        return ['status' => TrainingBlockRunStatus::class, 'started_at' => 'datetime', 'ended_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $run) => $run->uuid ??= (string) Str::uuid());
    }

    public function trainingRun(): BelongsTo
    {
        return $this->belongsTo(TrainingRun::class);
    }

    public function trainingBlock(): BelongsTo
    {
        return $this->belongsTo(TrainingBlock::class);
    }
}
