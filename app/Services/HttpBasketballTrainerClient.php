<?php

namespace App\Services;

use App\Contracts\BasketballTrainerClient;
use App\Enums\BasketballTrainerEmbedView;
use App\Exceptions\BasketballTrainerException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

class HttpBasketballTrainerClient implements BasketballTrainerClient
{
    /** @return list<array<string, mixed>> */
    public function listPlaybooks(): array
    {
        $data = $this->request('get', 'api/integrations/v1/playbooks');

        if (! array_is_list($data)) {
            throw new BasketballTrainerException(
                BasketballTrainerException::InvalidResponse,
                'The playbook collection is not a list.',
            );
        }

        foreach ($data as $playbook) {
            if (! is_array($playbook)) {
                throw new BasketballTrainerException(
                    BasketballTrainerException::InvalidResponse,
                    'The playbook collection contains an invalid item.',
                );
            }

            $this->validatePlaybook($playbook);
        }

        return $data;
    }

    /** @return array<string, mixed> */
    public function getPlaybook(string $playbookHash): array
    {
        $data = $this->request(
            'get',
            'api/integrations/v1/playbooks/'.rawurlencode($playbookHash),
        );

        $this->validatePlaybook($data);

        return $data;
    }

    /** @return array{url: string, expires_at: string} */
    public function createEmbedSession(
        string $playbookHash,
        string $locale = 'nl',
        string $theme = 'system',
        BasketballTrainerEmbedView $view = BasketballTrainerEmbedView::Inline,
    ): array {
        $data = $this->request(
            'post',
            'api/integrations/v1/playbooks/'.rawurlencode($playbookHash).'/embed-session',
            [
                'locale' => $locale,
                'theme' => $theme,
                'view' => $view->value,
            ],
        );

        if (
            ! isset($data['url'], $data['expires_at'])
            || ! is_string($data['url'])
            || ! is_string($data['expires_at'])
            || ! $this->isTrustedEmbedUrl($data['url'])
        ) {
            throw new BasketballTrainerException(
                BasketballTrainerException::InvalidResponse,
                'The embed session response is invalid.',
            );
        }

        return [
            'url' => $data['url'],
            'expires_at' => $data['expires_at'],
        ];
    }

    /** @return array<mixed> */
    private function request(string $method, string $path, array $payload = []): array
    {
        $baseUrl = rtrim((string) config('services.basketball_trainer.url'), '/');
        $token = (string) config('services.basketball_trainer.token');

        if ($baseUrl === '' || $token === '') {
            throw new BasketballTrainerException(
                BasketballTrainerException::NotConfigured,
                'BasketballTrainer URL or token is missing.',
            );
        }

        try {
            $request = Http::baseUrl($baseUrl)
                ->acceptJson()
                ->withToken($token)
                ->connectTimeout(max(1, (int) config('services.basketball_trainer.connect_timeout', 2)))
                ->timeout(max(1, (int) config('services.basketball_trainer.timeout', 5)))
                ->retry(
                    [100, 300],
                    fn (Throwable $exception, PendingRequest $request): bool => $exception instanceof ConnectionException
                        || ($exception instanceof RequestException && $exception->response->serverError()),
                    throw: false,
                );

            $response = $method === 'post'
                ? $request->post($path, $payload)
                : $request->get($path, $payload);
        } catch (ConnectionException $exception) {
            throw new BasketballTrainerException(
                BasketballTrainerException::Unavailable,
                'BasketballTrainer connection failed.',
                $exception,
            );
        }

        $this->assertSuccessful($response);
        $data = $response->json('data');

        if (! is_array($data)) {
            throw new BasketballTrainerException(
                BasketballTrainerException::InvalidResponse,
                'BasketballTrainer response does not contain data.',
            );
        }

        return $data;
    }

    private function assertSuccessful(Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        $reason = match (true) {
            $response->unauthorized(), $response->forbidden() => BasketballTrainerException::Unauthorized,
            $response->notFound() => BasketballTrainerException::NotFound,
            $response->serverError() => BasketballTrainerException::Unavailable,
            default => BasketballTrainerException::InvalidResponse,
        };

        throw new BasketballTrainerException(
            $reason,
            "BasketballTrainer returned HTTP {$response->status()}.",
        );
    }

    /** @param array<mixed> $playbook */
    private function validatePlaybook(array $playbook): void
    {
        if (
            ! isset($playbook['id'], $playbook['title'], $playbook['sections'])
            || ! is_string($playbook['id'])
            || ! is_string($playbook['title'])
            || ! is_array($playbook['sections'])
        ) {
            throw new BasketballTrainerException(
                BasketballTrainerException::InvalidResponse,
                'BasketballTrainer playbook data is invalid.',
            );
        }
    }

    private function isTrustedEmbedUrl(string $url): bool
    {
        $baseUrl = (string) config('services.basketball_trainer.url');

        return filter_var($url, FILTER_VALIDATE_URL) !== false
            && $this->origin($url) !== null
            && $this->origin($url) === $this->origin($baseUrl);
    }

    private function origin(string $url): ?string
    {
        $parts = parse_url($url);

        if (
            ! is_array($parts)
            || ! isset($parts['scheme'], $parts['host'])
            || isset($parts['user'], $parts['pass'])
        ) {
            return null;
        }

        $scheme = strtolower($parts['scheme']);

        if (! in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);

        return $scheme.'://'.strtolower($parts['host']).':'.$port;
    }
}
