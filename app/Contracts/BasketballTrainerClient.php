<?php

namespace App\Contracts;

interface BasketballTrainerClient
{
    /** @return list<array<string, mixed>> */
    public function listPlaybooks(): array;

    /** @return array<string, mixed> */
    public function getPlaybook(string $playbookHash): array;

    /** @return array{url: string, expires_at: string} */
    public function createEmbedSession(
        string $playbookHash,
        string $locale = 'nl',
        string $theme = 'system',
    ): array;
}
