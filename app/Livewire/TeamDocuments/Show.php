<?php

namespace App\Livewire\TeamDocuments;

use App\Actions\BasketballTrainer\CreateEmbedSession;
use App\Actions\BasketballTrainer\LinkPlaybook;
use App\Actions\BasketballTrainer\RefreshPlaybook;
use App\Actions\BasketballTrainer\UnlinkPlaybook;
use App\Contracts\BasketballTrainerClient;
use App\Exceptions\BasketballTrainerException;
use App\Models\BasketballTrainerPlaybookLink;
use App\Models\TeamDocument;
use App\Services\PdfTableOfContentsExtractor;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class Show extends Component
{
    use WithFileUploads;

    public string $type;

    public ?TemporaryUploadedFile $pdf = null;

    public bool $showBasketballTrainerModal = false;

    /** @var list<array{id: string, title: string, season: ?string, plays_count: int}> */
    public array $availableBasketballTrainerPlaybooks = [];

    public string $selectedBasketballTrainerPlaybook = '';

    public ?string $basketballTrainerError = null;

    public ?string $basketballTrainerEmbedUrl = null;

    public ?string $basketballTrainerEmbedExpiresAt = null;

    public function mount(string $type, CreateEmbedSession $createEmbedSession): void
    {
        abort_unless(array_key_exists($type, TeamDocument::defaultRows()), 404);

        $this->type = $type;

        if ($type !== TeamDocument::Playbook) {
            return;
        }

        $document = TeamDocument::findByType($type);

        if (! $document->exists) {
            return;
        }

        $link = $document->basketballTrainerPlaybookLink()->first();

        if ($link) {
            $this->selectedBasketballTrainerPlaybook = $link->external_playbook_hash;
            $this->createBasketballTrainerEmbedSession($link, $createEmbedSession);
        }
    }

    public function save(PdfTableOfContentsExtractor $extractor): void
    {
        abort_unless(auth()->user()?->isCoach(), 403);

        TeamDocument::ensureDefaults();

        $validated = $this->validate([
            'pdf' => ['required', File::types(['pdf'])->max(20 * 1024)],
        ]);

        $document = TeamDocument::findByType($this->type);

        if ($document->pdf_path) {
            Storage::disk('local')->delete($document->pdf_path);
        }

        $filename = Str::slug($document->type).'-'.now()->format('YmdHis').'.pdf';
        $path = $validated['pdf']->storeAs("team-documents/{$document->type}", $filename, 'local');
        $result = $extractor->extract(Storage::disk('local')->path($path));

        $document->update([
            'pdf_path' => $path,
            'original_filename' => $validated['pdf']->getClientOriginalName(),
            'uploaded_by_user_id' => auth()->id(),
            'uploaded_at' => now(),
            'toc_status' => $result['status'],
            'toc_error' => $result['error'],
        ]);

        $document->sections()->delete();
        $document->sections()->createMany(
            collect($result['sections'])
                ->values()
                ->map(fn (array $section, int $index): array => $section + ['sort_order' => $index + 1])
                ->all(),
        );

        $this->pdf = null;
        $this->dispatch('team-document-saved');
    }

    public function openBasketballTrainerModal(BasketballTrainerClient $client): void
    {
        $this->authorizeBasketballTrainerManagement();
        $this->basketballTrainerError = null;

        try {
            $this->availableBasketballTrainerPlaybooks = collect($client->listPlaybooks())
                ->map(fn (array $playbook): array => [
                    'id' => (string) $playbook['id'],
                    'title' => (string) $playbook['title'],
                    'season' => filled($playbook['season'] ?? null) ? (string) $playbook['season'] : null,
                    'plays_count' => (int) ($playbook['plays_count'] ?? 0),
                ])
                ->values()
                ->all();
            $this->showBasketballTrainerModal = true;
        } catch (BasketballTrainerException $exception) {
            $this->basketballTrainerError = $exception->userMessage();
        }
    }

    public function linkBasketballTrainerPlaybook(
        LinkPlaybook $linkPlaybook,
        CreateEmbedSession $createEmbedSession,
    ): void {
        $this->authorizeBasketballTrainerManagement();

        $validated = $this->validate([
            'selectedBasketballTrainerPlaybook' => ['required', 'string', 'max:64'],
        ], [
            'selectedBasketballTrainerPlaybook.required' => 'Kies een BasketballTrainer-playbook.',
        ]);

        TeamDocument::ensureDefaults();
        $document = TeamDocument::findByType($this->type);

        try {
            $link = $linkPlaybook->execute(
                $document,
                auth()->user(),
                $validated['selectedBasketballTrainerPlaybook'],
            );
            $this->basketballTrainerError = null;
            $this->showBasketballTrainerModal = false;
            $this->createBasketballTrainerEmbedSession($link, $createEmbedSession);
            $this->dispatch('basketball-trainer-linked');
        } catch (BasketballTrainerException $exception) {
            $this->basketballTrainerError = $exception->userMessage();
        }
    }

    public function refreshBasketballTrainerPlaybook(
        RefreshPlaybook $refreshPlaybook,
        CreateEmbedSession $createEmbedSession,
    ): void {
        $this->authorizeBasketballTrainerManagement();
        $link = $this->basketballTrainerLink();

        try {
            $link = $refreshPlaybook->execute($link);
            $this->basketballTrainerError = null;
            $this->createBasketballTrainerEmbedSession($link, $createEmbedSession);
            $this->dispatch('basketball-trainer-refreshed');
        } catch (BasketballTrainerException $exception) {
            $this->basketballTrainerError = $exception->userMessage();
        }
    }

    public function reloadBasketballTrainerViewer(CreateEmbedSession $createEmbedSession): void
    {
        $this->basketballTrainerError = null;
        $this->createBasketballTrainerEmbedSession(
            $this->basketballTrainerLink(),
            $createEmbedSession,
        );
    }

    public function unlinkBasketballTrainerPlaybook(UnlinkPlaybook $unlinkPlaybook): void
    {
        $this->authorizeBasketballTrainerManagement();
        $document = TeamDocument::findByType($this->type);

        if ($document->exists) {
            $unlinkPlaybook->execute($document);
        }

        $this->availableBasketballTrainerPlaybooks = [];
        $this->selectedBasketballTrainerPlaybook = '';
        $this->basketballTrainerEmbedUrl = null;
        $this->basketballTrainerEmbedExpiresAt = null;
        $this->basketballTrainerError = null;
        $this->dispatch('basketball-trainer-unlinked');
    }

    public function render(): View
    {
        $document = TeamDocument::findByType($this->type)->load([
            'sections',
            'uploadedBy',
            'basketballTrainerPlaybookLink',
        ]);
        $hasPdf = $document->pdf_path && Storage::disk('local')->exists($document->pdf_path);
        $routePrefix = auth()->user()?->canAccessCoachArea() ? 'coach' : 'player';

        return view('livewire.team-documents.show', [
            'document' => $document,
            'basketballTrainerLink' => $document->basketballTrainerPlaybookLink,
            'hasPdf' => $hasPdf,
            'pdfUrl' => $hasPdf ? route($routePrefix.'.documents.pdf', $document, absolute: false) : null,
        ])->layout('layouts.app');
    }

    private function authorizeBasketballTrainerManagement(): void
    {
        abort_unless($this->type === TeamDocument::Playbook, 404);
        Gate::authorize('manage-coach-area');
    }

    private function basketballTrainerLink(): BasketballTrainerPlaybookLink
    {
        abort_unless($this->type === TeamDocument::Playbook, 404);

        $document = TeamDocument::findByType($this->type);
        abort_unless($document->exists, 404);

        $link = $document->basketballTrainerPlaybookLink()->first();
        abort_unless($link, 404);

        return $link;
    }

    private function createBasketballTrainerEmbedSession(
        BasketballTrainerPlaybookLink $link,
        CreateEmbedSession $createEmbedSession,
    ): void {
        try {
            $session = $createEmbedSession->execute($link);
            $this->basketballTrainerEmbedUrl = $session['url'];
            $this->basketballTrainerEmbedExpiresAt = $session['expires_at'];
        } catch (BasketballTrainerException $exception) {
            $this->basketballTrainerEmbedUrl = null;
            $this->basketballTrainerEmbedExpiresAt = null;
            $this->basketballTrainerError = $exception->userMessage();
        }
    }
}
