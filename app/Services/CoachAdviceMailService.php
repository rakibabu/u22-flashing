<?php

namespace App\Services;

use App\Mail\CoachAdviceWritten;
use App\Models\CoachNote;
use Illuminate\Support\Facades\Mail;

class CoachAdviceMailService
{
    public function sendWhenVisible(CoachNote $coachNote): void
    {
        if (! $coachNote->visible_to_player || $coachNote->sent_at !== null) {
            return;
        }

        $coachNote->loadMissing('player.user');

        $email = $coachNote->player->user?->email;

        if (! filled($email)) {
            return;
        }

        Mail::to($email)->send(new CoachAdviceWritten($coachNote));

        $coachNote->forceFill(['sent_at' => now()])->save();
    }
}
