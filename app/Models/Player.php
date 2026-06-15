<?php

namespace App\Models;

use Database\Factories\PlayerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Player extends Model
{
    /** @use HasFactory<PlayerFactory> */
    use HasFactory;

    public const Conditioning = 'conditioning';

    public const MuscleGain = 'muscle_gain';

    public const Maintenance = 'maintenance';

    public const GuardDevelopment = 'guard_development';

    protected $fillable = [
        'user_id',
        'name',
        'active',
        'program_type',
        'date_of_birth',
        'age',
        'height_cm',
        'start_weight_kg',
        'target_weight_kg',
        'long_term_target_weight_kg',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'date_of_birth' => 'date',
            'start_weight_kg' => 'decimal:2',
            'target_weight_kg' => 'decimal:2',
            'long_term_target_weight_kg' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function settings(): HasOne
    {
        return $this->hasOne(PlayerProgramSetting::class);
    }

    public function invites(): HasMany
    {
        return $this->hasMany(Invite::class);
    }

    public function checkins(): HasMany
    {
        return $this->hasMany(WeeklyCheckin::class);
    }

    public function testResults(): HasMany
    {
        return $this->hasMany(TestResult::class);
    }

    public function coachNotes(): HasMany
    {
        return $this->hasMany(CoachNote::class);
    }

    public function latestInvite(): HasOne
    {
        return $this->hasOne(Invite::class)->latestOfMany();
    }

    public function latestCheckin(): HasOne
    {
        return $this->hasOne(WeeklyCheckin::class)->latestOfMany('week_start_date');
    }

    public function programName(): string
    {
        return match ($this->program_type) {
            self::Conditioning => 'Conditie',
            self::MuscleGain => 'Bulk/kracht',
            self::GuardDevelopment => 'Guard development',
            default => 'Onderhoud',
        };
    }

    public function isMuscleGain(): bool
    {
        return $this->program_type === self::MuscleGain;
    }

    public function isConditioning(): bool
    {
        return $this->program_type === self::Conditioning;
    }

    public function isGuardDevelopment(): bool
    {
        return $this->program_type === self::GuardDevelopment;
    }

    public function tracksNutrition(): bool
    {
        return $this->isMuscleGain() || $this->isGuardDevelopment();
    }
}
