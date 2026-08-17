<?php

namespace App\Models;

use Database\Factories\TrainingRunFeedbackFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingRunFeedback extends Model
{
    /** @use HasFactory<TrainingRunFeedbackFactory> */
    use HasFactory;

    protected $fillable = ['author_name', 'feedback'];

    public function trainingRun(): BelongsTo
    {
        return $this->belongsTo(TrainingRun::class);
    }
}
