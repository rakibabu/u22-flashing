<?php

namespace App\Models;

use Database\Factories\TeamDocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['type', 'title', 'description', 'pdf_path', 'original_filename', 'uploaded_by_user_id', 'uploaded_at', 'toc_status', 'toc_error'])]
#[UseFactory(TeamDocumentFactory::class)]
class TeamDocument extends Model
{
    /** @use HasFactory<TeamDocumentFactory> */
    use HasFactory;

    public const Plays = 'plays';

    public const Playbook = 'playbook';

    public const TeamAgreements = 'team-afspraken';

    public const TocMissing = 'missing';

    public const TocGenerated = 'generated';

    public const TocFallback = 'fallback';

    public const TocFailed = 'failed';

    /**
     * @return array<string, array{title:string,description:string}>
     */
    public static function defaultRows(): array
    {
        return [
            self::Plays => [
                'title' => 'Plays',
                'description' => 'Teamplays en wedstrijdsituaties die spelers direct kunnen bekijken.',
            ],
            self::Playbook => [
                'title' => 'Playbook',
                'description' => 'Het volledige playbook als inline PDF met automatische inhoudsopgave.',
            ],
            self::TeamAgreements => [
                'title' => 'Team afspraken',
                'description' => 'Teamregels, afspraken en praktische informatie voor spelers.',
            ],
        ];
    }

    /**
     * @return Collection<int, self>
     */
    public static function ensureDefaults(): Collection
    {
        foreach (self::defaultRows() as $type => $attributes) {
            self::query()->firstOrCreate(['type' => $type], $attributes);
        }

        $order = array_keys(self::defaultRows());

        return self::query()
            ->whereIn('type', $order)
            ->get()
            ->sortBy(function (self $document) use ($order): int {
                $position = array_search($document->type, $order, true);

                return $position === false ? PHP_INT_MAX : $position;
            })
            ->values();
    }

    public static function findByType(string $type): self
    {
        abort_unless(array_key_exists($type, self::defaultRows()), 404);

        self::ensureDefaults();

        return self::query()->where('type', $type)->firstOrFail();
    }

    public function getRouteKeyName(): string
    {
        return 'type';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
        ];
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(TeamDocumentSection::class)->orderBy('sort_order');
    }
}
