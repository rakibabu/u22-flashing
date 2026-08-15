<?php

namespace App\Models;

use App\Enums\ExerciseScope;
use App\Enums\TrainingCoach;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ExerciseLibraryItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category',
        'name',
        'description',
        'execution',
        'coaching_cues',
        'common_mistakes',
        'sort_order',
        'scope',
        'objective',
        'organization',
        'default_duration_minutes',
        'min_players',
        'max_players',
        'baskets_required',
        'intensity',
        'materials',
        'coaching_points',
        'constraints',
        'regressions',
        'progressions',
        'tags',
        'coach_notes',
        'media_path',
        'media_type',
        'video_url',
        'external_url',
        'created_by',
        'default_coach',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'scope' => ExerciseScope::class,
            'default_coach' => TrainingCoach::class,
            'materials' => 'array',
            'coaching_points' => 'array',
            'constraints' => 'array',
            'regressions' => 'array',
            'progressions' => 'array',
            'tags' => 'array',
            'archived_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $exercise): void {
            $exercise->uuid ??= (string) Str::uuid();
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function trainingBlocks(): HasMany
    {
        return $this->hasMany(TrainingBlock::class);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('archived_at');
    }

    public function archive(): void
    {
        $this->update(['archived_at' => now()]);
    }
}
