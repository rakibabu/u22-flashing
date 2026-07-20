<?php

use App\Contracts\BasketballTrainerClient;
use App\Enums\BasketballTrainerEmbedView;
use App\Exceptions\BasketballTrainerException;
use App\Livewire\TeamDocuments\Show;
use App\Models\BasketballTrainerPlaybookLink;
use App\Models\Player;
use App\Models\TeamDocument;
use App\Models\User;
use App\Services\HttpBasketballTrainerClient;
use App\Support\BasketballTrainerPlaybookLinkData;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/** @return array<string, mixed> */
function basketballTrainerPlaybookData(string $hash = 'playbook-demo'): array
{
    return [
        'schema_version' => 1,
        'id' => $hash,
        'title' => 'U22 Motion Offense',
        'season' => '2026/2027',
        'age_group' => 'U22',
        'plays_count' => 2,
        'updated_at' => '2026-07-20T10:00:00+00:00',
        'revision' => 'revision-1',
        'edit_url' => 'https://trainer.example/playbooks/'.$hash.'/edit',
        'sections' => [
            [
                'title' => 'Half court',
                'plays' => [
                    ['id' => 'play-1', 'title' => 'Horns'],
                    ['id' => 'play-2', 'title' => 'Flex'],
                ],
            ],
        ],
    ];
}

/**
 * @param  list<array<string, mixed>>|null  $playbooks
 * @param  array<string, mixed>|null  $playbook
 * @param  array{url: string, expires_at: string}|null  $embedSession
 */
function bindBasketballTrainerClient(
    ?array $playbooks = null,
    ?array $playbook = null,
    ?array $embedSession = null,
    ?BasketballTrainerException $exception = null,
): void {
    $playbook ??= basketballTrainerPlaybookData();
    $playbooks ??= [$playbook];
    $embedSession ??= [
        'url' => 'https://trainer.example/embed/u22/playbook-demo',
        'expires_at' => '2026-07-20T10:15:00+00:00',
    ];

    app()->instance(BasketballTrainerClient::class, new class($playbooks, $playbook, $embedSession, $exception) implements BasketballTrainerClient
    {
        /**
         * @param  list<array<string, mixed>>  $playbooks
         * @param  array<string, mixed>  $playbook
         * @param  array{url: string, expires_at: string}  $embedSession
         */
        public function __construct(
            private array $playbooks,
            private array $playbook,
            private array $embedSession,
            private ?BasketballTrainerException $exception,
        ) {}

        public function listPlaybooks(): array
        {
            $this->throwWhenUnavailable();

            return $this->playbooks;
        }

        public function getPlaybook(string $playbookHash): array
        {
            $this->throwWhenUnavailable();

            return [...$this->playbook, 'id' => $playbookHash];
        }

        public function createEmbedSession(
            string $playbookHash,
            string $locale = 'nl',
            string $theme = 'system',
            BasketballTrainerEmbedView $view = BasketballTrainerEmbedView::Inline,
        ): array {
            $this->throwWhenUnavailable();

            return $this->embedSession;
        }

        private function throwWhenUnavailable(): void
        {
            if ($this->exception) {
                throw $this->exception;
            }
        }
    });
}

function basketballTrainerPlayer(): User
{
    $user = User::factory()->player()->create();
    Player::factory()->create(['user_id' => $user->id]);

    return $user;
}

test('coach kan een BasketballTrainer playbook kiezen en koppelen', function () {
    TeamDocument::ensureDefaults();
    bindBasketballTrainerClient();
    $coach = User::factory()->coach()->create();

    Livewire::actingAs($coach)
        ->test(Show::class, ['type' => TeamDocument::Playbook])
        ->call('openBasketballTrainerModal')
        ->assertSet('showBasketballTrainerModal', true)
        ->assertSee('U22 Motion Offense')
        ->set('selectedBasketballTrainerPlaybook', 'playbook-demo')
        ->call('linkBasketballTrainerPlaybook')
        ->assertHasNoErrors()
        ->assertSet('showBasketballTrainerModal', false)
        ->assertSet('basketballTrainerEmbedUrl', 'https://trainer.example/embed/u22/playbook-demo')
        ->assertDispatched('basketball-trainer-linked');

    $link = BasketballTrainerPlaybookLink::query()->firstOrFail();

    expect($link->external_playbook_hash)->toBe('playbook-demo')
        ->and($link->external_title)->toBe('U22 Motion Offense')
        ->and($link->metadata['plays_count'])->toBe(2)
        ->and($link->metadata['edit_url'])->toBe('https://trainer.example/playbooks/playbook-demo/edit')
        ->and($link->linked_by_user_id)->toBe($coach->id)
        ->and($link->last_error)->toBeNull();
});

test('onveilige externe bewerklinks worden niet opgeslagen', function () {
    $playbook = basketballTrainerPlaybookData();
    $playbook['edit_url'] = 'javascript:alert(1)';

    $attributes = app(BasketballTrainerPlaybookLinkData::class)->attributes($playbook);

    expect($attributes['metadata']['edit_url'])->toBeNull();
});

test('gekoppeld playbook wordt voor spelers als beveiligde embed getoond', function () {
    TeamDocument::ensureDefaults();
    $document = TeamDocument::findByType(TeamDocument::Playbook);
    BasketballTrainerPlaybookLink::factory()->create([
        'team_document_id' => $document->id,
        'external_playbook_hash' => 'playbook-demo',
        'external_title' => 'U22 Motion Offense',
    ]);
    bindBasketballTrainerClient();

    $this->actingAs(basketballTrainerPlayer())
        ->get(route('player.documents.show', TeamDocument::Playbook))
        ->assertOk()
        ->assertSee('Live vanuit BasketballTrainer')
        ->assertSee('basketball-trainer-embed', false)
        ->assertSee('https://trainer.example/embed/u22/playbook-demo', false)
        ->assertSee('sandbox="allow-scripts allow-same-origin"', false)
        ->assertDontSee('Playbook koppelen');
});

test('coach viewer kan de koppeling bekijken maar niet beheren', function () {
    TeamDocument::ensureDefaults();
    bindBasketballTrainerClient();

    Livewire::actingAs(User::factory()->coachViewer()->create())
        ->test(Show::class, ['type' => TeamDocument::Playbook])
        ->assertDontSee('Playbook koppelen')
        ->call('openBasketballTrainerModal')
        ->assertForbidden();
});

test('bestaande PDF blijft beschikbaar als BasketballTrainer niet bereikbaar is', function () {
    Storage::fake('local');
    TeamDocument::ensureDefaults();
    $document = TeamDocument::findByType(TeamDocument::Playbook);
    $document->update([
        'pdf_path' => 'team-documents/playbook/fallback.pdf',
        'original_filename' => 'fallback.pdf',
    ]);
    Storage::disk('local')->put($document->pdf_path, '%PDF-1.4 fallback');
    BasketballTrainerPlaybookLink::factory()->create([
        'team_document_id' => $document->id,
        'external_playbook_hash' => 'playbook-demo',
    ]);
    bindBasketballTrainerClient(exception: new BasketballTrainerException(
        BasketballTrainerException::Unavailable,
        'Connection failed.',
    ));

    $this->actingAs(basketballTrainerPlayer())
        ->get(route('player.documents.show', TeamDocument::Playbook))
        ->assertOk()
        ->assertSee('BasketballTrainer is tijdelijk niet bereikbaar.')
        ->assertSee('Opnieuw proberen')
        ->assertSee('Open PDF')
        ->assertSee(route('player.documents.pdf', $document, absolute: false), false);

    expect(BasketballTrainerPlaybookLink::query()->firstOrFail()->last_error)
        ->toBe('BasketballTrainer is tijdelijk niet bereikbaar.');
});

test('coach kan de koppeling vernieuwen en ontkoppelen', function () {
    TeamDocument::ensureDefaults();
    $document = TeamDocument::findByType(TeamDocument::Playbook);
    $link = BasketballTrainerPlaybookLink::factory()->create([
        'team_document_id' => $document->id,
        'external_playbook_hash' => 'playbook-demo',
        'external_title' => 'Oude titel',
    ]);
    bindBasketballTrainerClient();

    Livewire::actingAs(User::factory()->coach()->create())
        ->test(Show::class, ['type' => TeamDocument::Playbook])
        ->call('refreshBasketballTrainerPlaybook')
        ->assertDispatched('basketball-trainer-refreshed')
        ->call('unlinkBasketballTrainerPlaybook')
        ->assertSet('basketballTrainerEmbedUrl', null)
        ->assertDispatched('basketball-trainer-unlinked');

    expect($link->fresh())->toBeNull();
});

test('HTTP client gebruikt bearer authenticatie en versie 1 contract', function () {
    config()->set('services.basketball_trainer.url', 'https://trainer.example');
    config()->set('services.basketball_trainer.token', 'u22-secret-token');
    Http::fake([
        'https://trainer.example/api/integrations/v1/playbooks' => Http::response([
            'data' => [basketballTrainerPlaybookData()],
        ]),
    ]);

    $playbooks = (new HttpBasketballTrainerClient)->listPlaybooks();

    expect($playbooks)->toHaveCount(1)
        ->and($playbooks[0]['schema_version'])->toBe(1);

    Http::assertSent(fn ($request): bool => $request->url() === 'https://trainer.example/api/integrations/v1/playbooks'
        && $request->hasHeader('Authorization', 'Bearer u22-secret-token'));
});

test('HTTP client vraagt standaard de compacte embedweergave aan', function () {
    config()->set('services.basketball_trainer.url', 'https://trainer.example');
    config()->set('services.basketball_trainer.token', 'u22-secret-token');
    Http::fake([
        'https://trainer.example/api/integrations/v1/playbooks/playbook-demo/embed-session' => Http::response([
            'data' => [
                'url' => 'https://trainer.example/embed/u22/playbook-demo',
                'expires_at' => '2026-07-20T10:15:00+00:00',
            ],
        ]),
    ]);

    (new HttpBasketballTrainerClient)->createEmbedSession('playbook-demo');

    Http::assertSent(fn ($request): bool => $request->method() === 'POST'
        && $request->data() === [
            'locale' => 'nl',
            'theme' => 'system',
            'view' => 'inline',
        ]);
});

test('HTTP client vertaalt geweigerde tokens naar een veilige domeinfout', function () {
    config()->set('services.basketball_trainer.url', 'https://trainer.example');
    config()->set('services.basketball_trainer.token', 'invalid-token');
    Http::fake(['*' => Http::response([], 401)]);

    try {
        (new HttpBasketballTrainerClient)->listPlaybooks();
        $this->fail('Expected BasketballTrainerException was not thrown.');
    } catch (BasketballTrainerException $exception) {
        expect($exception->reason)->toBe(BasketballTrainerException::Unauthorized)
            ->and($exception->userMessage())->toBe('BasketballTrainer heeft het integratietoken geweigerd.');
    }
});

test('HTTP client weigert een embedsessie van een ander origin', function () {
    config()->set('services.basketball_trainer.url', 'https://trainer.example');
    config()->set('services.basketball_trainer.token', 'u22-secret-token');
    Http::fake([
        '*' => Http::response([
            'data' => [
                'url' => 'https://attacker.example/embed/playbook-demo',
                'expires_at' => '2026-07-20T10:15:00+00:00',
            ],
        ]),
    ]);

    expect(fn () => (new HttpBasketballTrainerClient)->createEmbedSession('playbook-demo'))
        ->toThrow(BasketballTrainerException::class, 'embed session response is invalid');
});
