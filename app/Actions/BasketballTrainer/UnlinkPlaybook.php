<?php

namespace App\Actions\BasketballTrainer;

use App\Models\TeamDocument;
use Illuminate\Support\Facades\Cache;

class UnlinkPlaybook
{
    public function execute(TeamDocument $document): void
    {
        Cache::lock('basketball-trainer-playbook-link:'.$document->id, 10)
            ->block(3, fn () => $document->basketballTrainerPlaybookLink()->delete());
    }
}
