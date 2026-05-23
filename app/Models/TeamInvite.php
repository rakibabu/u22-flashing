<?php

namespace App\Models;

use Database\Factories\TeamInviteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TeamInvite extends Model
{
    /** @use HasFactory<TeamInviteFactory> */
    use HasFactory;

    protected $fillable = [
        'created_by_user_id',
        'token_hash',
        'expires_at',
        'revoked_at',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'last_used_at' => 'datetime',
        ];
    }

    public static function createForCoach(User $coach, int $days = 14): array
    {
        $token = Str::random(48);

        $teamInvite = self::query()->create([
            'created_by_user_id' => $coach->id,
            'token_hash' => self::hashToken($token),
            'expires_at' => now()->addDays($days),
        ]);

        return [$teamInvite, $token];
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function usable(): bool
    {
        return $this->revoked_at === null && $this->expires_at->isFuture();
    }

    public function statusLabel(): string
    {
        if ($this->revoked_at !== null) {
            return 'Ingetrokken';
        }

        if ($this->expires_at->isPast()) {
            return 'Verlopen';
        }

        return 'Actief';
    }
}
