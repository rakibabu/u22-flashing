<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WeeklyCheckin;

class WeeklyCheckinPolicy
{
    public function view(User $user, WeeklyCheckin $weeklyCheckin): bool
    {
        return $user->isCoach() || $weeklyCheckin->player->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isPlayer();
    }

    public function update(User $user, WeeklyCheckin $weeklyCheckin): bool
    {
        return $user->isPlayer() && $weeklyCheckin->player->user_id === $user->id;
    }
}
