<?php

namespace App\Actions\BasketballTrainer;

use App\Contracts\BasketballTrainerClient;
use App\Models\BasketballTrainerPlaybookLink;
use App\Models\TeamDocument;
use App\Models\User;
use App\Support\BasketballTrainerPlaybookLinkData;
use Illuminate\Support\Facades\Cache;

class LinkPlaybook
{
    public function __construct(
        private BasketballTrainerClient $client,
        private BasketballTrainerPlaybookLinkData $linkData,
    ) {}

    public function execute(
        TeamDocument $document,
        User $user,
        string $playbookHash,
    ): BasketballTrainerPlaybookLink {
        abort_unless($document->type === TeamDocument::Playbook, 404);

        return Cache::lock('basketball-trainer-playbook-link:'.$document->id, 10)
            ->block(3, function () use ($document, $user, $playbookHash): BasketballTrainerPlaybookLink {
                $playbook = $this->client->getPlaybook($playbookHash);

                return $document->basketballTrainerPlaybookLink()->updateOrCreate([], [
                    ...$this->linkData->attributes($playbook),
                    'linked_by_user_id' => $user->id,
                ]);
            });
    }
}
