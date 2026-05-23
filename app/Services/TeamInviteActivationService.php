<?php

namespace App\Services;

use App\Exceptions\TeamInviteActivationException;
use App\Models\Player;
use App\Models\TeamInvite;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TeamInviteActivationService
{
    public function findUsableInvite(string $token): ?TeamInvite
    {
        $teamInvite = TeamInvite::query()
            ->where('token_hash', TeamInvite::hashToken($token))
            ->first();

        return $teamInvite?->usable() ? $teamInvite : null;
    }

    /**
     * @throws TeamInviteActivationException
     */
    public function claimablePlayerForName(string $name): Player
    {
        $matches = $this->matchingClaimablePlayers($name);

        if ($matches->count() !== 1) {
            throw new TeamInviteActivationException('Player name cannot be safely matched.');
        }

        return $matches->first();
    }

    /**
     * @param  array{username: string, email?: string|null, password: string}  $credentials
     *
     * @throws TeamInviteActivationException
     */
    public function activate(string $token, int $playerId, string $name, array $credentials): User
    {
        return DB::transaction(function () use ($token, $playerId, $name, $credentials): User {
            $teamInvite = TeamInvite::query()
                ->where('token_hash', TeamInvite::hashToken($token))
                ->lockForUpdate()
                ->first();

            if (! $teamInvite?->usable()) {
                throw new TeamInviteActivationException('Team invite is not usable.');
            }

            $matches = $this->matchingClaimablePlayers($name, lockForUpdate: true);

            if ($matches->count() !== 1 || $matches->first()->id !== $playerId) {
                throw new TeamInviteActivationException('Player name cannot be safely claimed.');
            }

            $player = $matches->first();
            $email = filled($credentials['email'] ?? null) ? $credentials['email'] : null;

            $user = User::query()->create([
                'name' => $player->name,
                'email' => $email,
                'username' => $credentials['username'],
                'password' => $credentials['password'],
                'role' => 'player',
                'email_verified_at' => $email ? now() : null,
            ]);

            $player->update(['user_id' => $user->id, 'active' => true]);
            $teamInvite->update(['last_used_at' => now()]);

            return $user;
        }, attempts: 3);
    }

    public function normalizedName(string $name): string
    {
        return Str::of(Str::ascii($name))
            ->squish()
            ->lower()
            ->toString();
    }

    /**
     * @return Collection<int, Player>
     */
    private function matchingClaimablePlayers(string $name, bool $lockForUpdate = false): Collection
    {
        $normalizedName = $this->normalizedName($name);
        $query = Player::query()
            ->where('active', true)
            ->whereNull('user_id');

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query
            ->get()
            ->filter(fn (Player $player): bool => $this->normalizedName($player->name) === $normalizedName)
            ->values();
    }
}
