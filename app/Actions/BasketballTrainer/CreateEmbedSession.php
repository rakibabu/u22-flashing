<?php

namespace App\Actions\BasketballTrainer;

use App\Contracts\BasketballTrainerClient;
use App\Enums\BasketballTrainerEmbedView;
use App\Exceptions\BasketballTrainerException;
use App\Models\BasketballTrainerPlaybookLink;

class CreateEmbedSession
{
    public function __construct(private BasketballTrainerClient $client) {}

    /** @return array{url: string, expires_at: string} */
    public function execute(
        BasketballTrainerPlaybookLink $link,
        string $theme = 'system',
    ): array {
        try {
            $session = $this->client->createEmbedSession(
                $link->external_playbook_hash,
                'nl',
                $theme,
                BasketballTrainerEmbedView::Inline,
            );
        } catch (BasketballTrainerException $exception) {
            $link->update([
                'last_checked_at' => now(),
                'last_error' => $exception->userMessage(),
            ]);

            throw $exception;
        }

        $link->update([
            'last_checked_at' => now(),
            'last_error' => null,
        ]);

        return $session;
    }
}
