<?php

namespace App\Models;

use App\Enums\TrainingRunStatus;
use Database\Factories\TrainingRunFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class TrainingRun extends Model
{
    /** @use HasFactory<TrainingRunFactory> */
    use HasFactory;

    protected $fillable = ['uuid', 'training_session_id', 'started_by', 'current_training_block_id', 'status', 'started_at', 'ended_at', 'paused_at', 'total_paused_seconds', 'general_notes', 'what_worked', 'what_to_change', 'next_action'];

    protected function casts(): array
    {
        return ['status' => TrainingRunStatus::class, 'started_at' => 'datetime', 'ended_at' => 'datetime', 'paused_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $run) => $run->uuid ??= (string) Str::uuid());
    }

    public function trainingSession(): BelongsTo
    {
        return $this->belongsTo(TrainingSession::class);
    }

    public function starter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    public function currentBlock(): BelongsTo
    {
        return $this->belongsTo(TrainingBlock::class, 'current_training_block_id');
    }

    public function blockRuns(): HasMany
    {
        return $this->hasMany(TrainingBlockRun::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(TrainingAttendance::class);
    }

    public function feedback(): HasMany
    {
        return $this->hasMany(TrainingRunFeedback::class)->latest();
    }

    public function elapsedSeconds(): int
    {
        $end = $this->ended_at ?? now();

        return max(0, $this->started_at->diffInSeconds($end) - $this->total_paused_seconds - ($this->paused_at ? $this->paused_at->diffInSeconds($end) : 0));
    }
}
