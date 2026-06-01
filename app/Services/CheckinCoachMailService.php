<?php

namespace App\Services;

use App\Mail\PlayerCheckinSubmitted;
use App\Models\User;
use App\Models\WeeklyCheckin;
use Illuminate\Support\Facades\Mail;

class CheckinCoachMailService
{
    public function sendOnce(WeeklyCheckin $weeklyCheckin): void
    {
        if ($weeklyCheckin->submitted_at === null || $weeklyCheckin->coach_notified_at !== null) {
            return;
        }

        $weeklyCheckin->loadMissing('player');

        $coaches = User::query()
            ->where('role', 'coach')
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->get();

        if ($coaches->isEmpty()) {
            return;
        }

        $coaches->each(function (User $coach) use ($weeklyCheckin): void {
            Mail::to($coach->email)->queue(new PlayerCheckinSubmitted($weeklyCheckin, $coach));
        });

        $weeklyCheckin->forceFill(['coach_notified_at' => now()])->save();
    }
}
