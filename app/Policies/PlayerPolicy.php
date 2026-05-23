<?php

namespace App\Policies;

use App\Models\Player;
use App\Models\User;

class PlayerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isCoach();
    }

    public function view(User $user, Player $player): bool
    {
        return $user->isCoach() || $player->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isCoach();
    }

    public function update(User $user, Player $player): bool
    {
        return $user->isCoach();
    }

    public function delete(User $user, Player $player): bool
    {
        return $user->isCoach();
    }
}
