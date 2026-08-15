<?php

namespace App\Models;

use App\Enums\TrainingAttendanceStatus;
use Database\Factories\TrainingAttendanceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingAttendance extends Model
{
    /** @use HasFactory<TrainingAttendanceFactory> */
    use HasFactory;

    protected $fillable = ['training_run_id', 'player_id', 'status', 'notes'];

    protected function casts(): array
    {
        return ['status' => TrainingAttendanceStatus::class];
    }

    public function trainingRun(): BelongsTo
    {
        return $this->belongsTo(TrainingRun::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
