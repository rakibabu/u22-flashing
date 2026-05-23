<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Invite extends Model
{
    protected $fillable = ['player_id', 'token_hash', 'expires_at', 'used_at'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public static function createForPlayer(Player $player, int $days = 14): array
    {
        $token = Str::random(48);

        $invite = $player->invites()->create([
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDays($days),
        ]);

        return [$invite, $token];
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function usable(): bool
    {
        return $this->used_at === null && $this->expires_at->isFuture();
    }
}
