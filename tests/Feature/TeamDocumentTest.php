<?php

use App\Livewire\TeamDocuments\Show;
use App\Models\Player;
use App\Models\TeamDocument;
use App\Models\User;
use App\Services\PdfTableOfContentsExtractor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function teamDocumentCoach(): User
{
    return User::factory()->coach()->create(['email' => 'team-document-coach@example.test']);
}

function teamDocumentPlayer(): User
{
    $user = User::factory()->player()->create(['email' => 'team-document-player@example.test']);
    Player::factory()->create(['user_id' => $user->id]);

    return $user;
}

function bindTeamDocumentExtractor(): void
{
    app()->bind(PdfTableOfContentsExtractor::class, fn () => new class extends PdfTableOfContentsExtractor
    {
        public function extract(string $pdfPath): array
        {
            return [
                'status' => TeamDocument::TocGenerated,
                'error' => null,
                'sections' => [
                    ['title' => 'Press break', 'page_number' => 2, 'source' => 'text'],
                    ['title' => 'Baseline out of bounds', 'page_number' => 5, 'source' => 'text'],
                ],
            ];
        }
    });
}

test('coach kan team document pdf uploaden en inhoudsopgave wordt opgeslagen', function () {
    Storage::fake('local');
    bindTeamDocumentExtractor();

    Livewire::actingAs(teamDocumentCoach())
        ->test(Show::class, ['type' => TeamDocument::Plays])
        ->set('pdf', UploadedFile::fake()->create('plays.pdf', 20, 'application/pdf'))
        ->call('save')
        ->assertDispatched('team-document-saved');

    $document = TeamDocument::query()->where('type', TeamDocument::Plays)->firstOrFail();

    expect($document->pdf_path)->not->toBeNull()
        ->and($document->original_filename)->toBe('plays.pdf')
        ->and($document->toc_status)->toBe(TeamDocument::TocGenerated)
        ->and($document->sections()->pluck('title')->all())->toBe([
            'Press break',
            'Baseline out of bounds',
        ]);

    Storage::disk('local')->assertExists($document->pdf_path);
});

test('alleen pdf uploads worden geaccepteerd', function () {
    Storage::fake('local');
    bindTeamDocumentExtractor();

    Livewire::actingAs(teamDocumentCoach())
        ->test(Show::class, ['type' => TeamDocument::Playbook])
        ->set('pdf', UploadedFile::fake()->create('playbook.txt', 4, 'text/plain'))
        ->call('save')
        ->assertHasErrors(['pdf']);
});

test('speler ziet team document met inhoudsopgave zonder uploadformulier', function () {
    Storage::fake('local');
    TeamDocument::ensureDefaults();

    $document = TeamDocument::query()->where('type', TeamDocument::TeamAgreements)->firstOrFail();
    $document->update([
        'pdf_path' => 'team-documents/team-afspraken/team.pdf',
        'original_filename' => 'team-afspraken.pdf',
        'toc_status' => TeamDocument::TocGenerated,
        'uploaded_at' => now(),
    ]);
    $document->sections()->createMany([
        ['title' => 'Teamregels', 'page_number' => 1, 'sort_order' => 1, 'source' => 'text'],
        ['title' => 'Communicatie', 'page_number' => 3, 'sort_order' => 2, 'source' => 'text'],
    ]);
    Storage::disk('local')->put($document->pdf_path, '%PDF-1.4 test');

    $this->actingAs(teamDocumentPlayer())
        ->get(route('player.documents.show', TeamDocument::TeamAgreements))
        ->assertOk()
        ->assertSee('Team afspraken')
        ->assertSee('Teamregels')
        ->assertSee('Communicatie')
        ->assertSee(route('player.documents.pdf', $document), false)
        ->assertDontSee('PDF uploaden');
});

test('pdf route toont document inline voor spelers', function () {
    Storage::fake('local');
    TeamDocument::ensureDefaults();

    $document = TeamDocument::query()->where('type', TeamDocument::Plays)->firstOrFail();
    $document->update([
        'pdf_path' => 'team-documents/plays/plays.pdf',
        'original_filename' => 'plays.pdf',
    ]);
    Storage::disk('local')->put($document->pdf_path, '%PDF-1.4 test');

    $this->actingAs(teamDocumentPlayer())
        ->get(route('player.documents.pdf', $document))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertHeader('content-disposition', 'inline; filename="plays.pdf"');
});

test('speler kan coach document route niet openen', function () {
    $this->actingAs(teamDocumentPlayer())
        ->get(route('coach.documents.show', TeamDocument::Plays))
        ->assertForbidden();
});
