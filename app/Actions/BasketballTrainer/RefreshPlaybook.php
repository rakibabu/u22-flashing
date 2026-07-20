<?php

namespace App\Actions\BasketballTrainer;

use App\Contracts\BasketballTrainerClient;
use App\Exceptions\BasketballTrainerException;
use App\Models\BasketballTrainerPlaybookLink;
use App\Support\BasketballTrainerPlaybookLinkData;

class RefreshPlaybook
{
    public function __construct(
        private BasketballTrainerClient $client,
        private BasketballTrainerPlaybookLinkData $linkData,
    ) {}

    public function execute(BasketballTrainerPlaybookLink $link): BasketballTrainerPlaybookLink
    {
        try {
            $playbook = $this->client->getPlaybook($link->external_playbook_hash);
        } catch (BasketballTrainerException $exception) {
            $link->update([
                'last_checked_at' => now(),
                'last_error' => $exception->userMessage(),
            ]);

            throw $exception;
        }

        $link->update($this->linkData->attributes($playbook));

        return $link->refresh();
    }
}
