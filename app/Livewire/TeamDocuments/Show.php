<?php

namespace App\Livewire\TeamDocuments;

use App\Models\TeamDocument;
use App\Services\PdfTableOfContentsExtractor;
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

    public function mount(string $type): void
    {
        abort_unless(array_key_exists($type, TeamDocument::defaultRows()), 404);

        $this->type = $type;
        TeamDocument::ensureDefaults();
    }

    public function save(PdfTableOfContentsExtractor $extractor): void
    {
        abort_unless(auth()->user()?->isCoach(), 403);

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

    public function render(): View
    {
        $document = TeamDocument::findByType($this->type)->load(['sections', 'uploadedBy']);
        $hasPdf = $document->pdf_path && Storage::disk('local')->exists($document->pdf_path);
        $routePrefix = auth()->user()?->isCoach() ? 'coach' : 'player';

        return view('livewire.team-documents.show', [
            'document' => $document,
            'hasPdf' => $hasPdf,
            'isCoach' => auth()->user()?->isCoach() === true,
            'pdfUrl' => $hasPdf ? route($routePrefix.'.documents.pdf', $document, absolute: false) : null,
        ])->layout('layouts.app');
    }
}
