<?php

namespace App\Services;

use App\Models\TeamDocument;
use Illuminate\Support\Str;
use Spatie\PdfToText\Pdf;
use Throwable;

class PdfTableOfContentsExtractor
{
    /**
     * @return array{status:string,error:?string,sections:array<int, array{title:string,page_number:int,source:string}>}
     */
    public function extract(string $pdfPath): array
    {
        try {
            $text = (new Pdf($this->binaryPath()))
                ->setOptions(['layout'])
                ->setTimeout(60)
                ->setPdf($pdfPath)
                ->text();
        } catch (Throwable $exception) {
            return [
                'status' => TeamDocument::TocFailed,
                'error' => $exception->getMessage(),
                'sections' => $this->fallbackSections(1),
            ];
        }

        $pages = $this->pages($text);
        $sections = $this->detectSections($pages);

        if ($sections === []) {
            return [
                'status' => TeamDocument::TocFallback,
                'error' => null,
                'sections' => $this->fallbackSections(max(1, count($pages))),
            ];
        }

        return [
            'status' => TeamDocument::TocGenerated,
            'error' => null,
            'sections' => $sections,
        ];
    }

    private function binaryPath(): ?string
    {
        $path = config('services.pdftotext.binary');

        return is_string($path) && $path !== '' ? $path : null;
    }

    /**
     * @return array<int, string>
     */
    private function pages(string $text): array
    {
        $pages = preg_split('/\f+/', $text) ?: [];

        return collect($pages)
            ->map(fn (string $page): string => trim($page))
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $pages
     * @return array<int, array{title:string,page_number:int,source:string}>
     */
    private function detectSections(array $pages): array
    {
        $sections = [];
        $seen = [];

        foreach ($pages as $index => $page) {
            $pageNumber = $index + 1;
            $pageSections = 0;

            foreach ($this->lines($page) as $line) {
                if (! $this->looksLikeHeading($line)) {
                    continue;
                }

                $key = Str::lower(Str::ascii($line));

                if (isset($seen[$key])) {
                    continue;
                }

                $seen[$key] = true;
                $sections[] = [
                    'title' => $line,
                    'page_number' => $pageNumber,
                    'source' => 'text',
                ];

                $pageSections++;

                if (count($sections) >= 40 || $pageSections >= 2) {
                    break;
                }
            }
        }

        return $sections;
    }

    /**
     * @return array<int, string>
     */
    private function lines(string $page): array
    {
        return collect(preg_split('/\R/u', $page) ?: [])
            ->map(fn (string $line): string => trim(preg_replace('/\s+/u', ' ', $line) ?? ''))
            ->filter(fn (string $line): bool => $line !== '')
            ->values()
            ->all();
    }

    private function looksLikeHeading(string $line): bool
    {
        $length = Str::length($line);

        if ($length < 4 || $length > 90) {
            return false;
        }

        if (str_word_count($line) > 12) {
            return false;
        }

        if (preg_match('/https?:\/\/|@|\d{2,}[-\/]\d{2,}/iu', $line)) {
            return false;
        }

        if (preg_match('/^(\d+[\).:-]\s+|[A-Z]\d?[\).:-]\s+).+/u', $line)) {
            return true;
        }

        if (preg_match('/^(hoofdstuk|chapter|fase|play|set|press|zone|man[- ]?to[- ]?man|aanval|verdediging|defen[cs]e|offen[cs]e|baseline|sideline|inbound|out of bounds|transition|regels|afspraken|team)\b/iu', $line)) {
            return true;
        }

        $letters = preg_replace('/[^A-Za-z]/', '', Str::ascii($line)) ?? '';

        return $letters !== '' && Str::upper($letters) === $letters && Str::length($letters) >= 5;
    }

    /**
     * @return array<int, array{title:string,page_number:int,source:string}>
     */
    private function fallbackSections(int $pageCount): array
    {
        return collect(range(1, $pageCount))
            ->map(fn (int $page): array => [
                'title' => $page === 1 ? 'Start document' : 'Pagina '.$page,
                'page_number' => $page,
                'source' => 'fallback',
            ])
            ->all();
    }
}
