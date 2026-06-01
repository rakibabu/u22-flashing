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
        $currentWeekStart = now()->startOfWeek();

        return $user->isPlayer()
            && $weeklyCheckin->player->user_id === $user->id
            && (
                $weeklyCheckin->week_start_date->isSameDay($currentWeekStart)
                || (
                    $weeklyCheckin->week_start_date->isSameDay($currentWeekStart->copy()->subWeek())
                    && now()->dayOfWeekIso <= 3
                )
            );
    }
}
