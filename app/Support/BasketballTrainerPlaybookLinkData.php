<?php

namespace App\Support;

class BasketballTrainerPlaybookLinkData
{
    /**
     * @param  array<string, mixed>  $playbook
     * @return array<string, mixed>
     */
    public function attributes(array $playbook): array
    {
        return [
            'external_playbook_hash' => $playbook['id'],
            'external_title' => $playbook['title'],
            'external_updated_at' => $playbook['updated_at'] ?? null,
            'metadata' => [
                'season' => $playbook['season'] ?? null,
                'age_group' => $playbook['age_group'] ?? null,
                'plays_count' => $playbook['plays_count'] ?? 0,
                'edit_url' => $this->safeExternalUrl($playbook['edit_url'] ?? null),
                'revision' => $playbook['revision'] ?? null,
                'sections' => collect($playbook['sections'] ?? [])
                    ->map(fn (array $section): array => [
                        'title' => $section['title'] ?? '',
                        'plays_count' => count($section['plays'] ?? []),
                    ])
                    ->all(),
            ],
            'last_checked_at' => now(),
            'last_error' => null,
        ];
    }

    private function safeExternalUrl(mixed $url): ?string
    {
        if (! is_string($url) || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        return in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)
            ? $url
            : null;
    }
}
