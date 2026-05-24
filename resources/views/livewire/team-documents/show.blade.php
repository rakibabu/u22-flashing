<div class="space-y-6">
    <x-page-header :title="$document->title" :description="$document->description" />

    @if ($isCoach)
        <section class="rounded-lg border border-primary-800/10 bg-white p-4 shadow-sm dark:border-flash-orange/20 dark:bg-primary-800">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase text-primary-700 dark:text-orange-100">Coach upload</p>
                    <h2 class="mt-1 font-display text-2xl font-normal leading-none text-primary-900 dark:text-white">PDF vervangen</h2>
                    <p class="mt-2 max-w-2xl text-sm text-zinc-600 dark:text-zinc-300">
                        Nieuwe versie voor spelers, inclusief automatische inhoudsopgave.
                    </p>
                    @if ($document->uploaded_at)
                        <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                            Laatst bijgewerkt {{ $document->uploaded_at->format('d-m-Y H:i') }}
                            @if ($document->uploadedBy)
                                door {{ $document->uploadedBy->name }}
                            @endif
                        </p>
                    @endif
                </div>

                <form wire:submit="save" class="w-full space-y-3 lg:max-w-sm">
                    <flux:field>
                        <flux:label>PDF uploaden</flux:label>
                        <input
                            wire:model="pdf"
                            type="file"
                            accept="application/pdf"
                            class="block w-full rounded-md border border-primary-100 bg-white px-3 py-2 text-sm text-primary-900 file:mr-3 file:rounded-md file:border-0 file:bg-primary-100 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-primary-900 dark:border-flash-orange/20"
                        >
                        <flux:error name="pdf" />
                    </flux:field>

                    <div class="flex flex-wrap items-center gap-2">
                        <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save,pdf">
                            Opslaan
                        </flux:button>
                        <span class="text-sm text-zinc-500" wire:loading wire:target="pdf">PDF voorbereiden...</span>
                        <span class="text-sm text-zinc-500" wire:loading wire:target="save">Inhoudsopgave maken...</span>
                    </div>
                </form>
            </div>

            @if ($document->toc_status === \App\Models\TeamDocument::TocFailed)
                <div class="mt-4 rounded-lg border border-flash-orange/30 bg-flash-orange/10 px-3 py-2 text-sm text-primary-900 dark:text-orange-50">
                    De PDF is opgeslagen, maar er kon geen automatische inhoudsopgave worden gemaakt.
                </div>
            @elseif ($document->toc_status === \App\Models\TeamDocument::TocFallback)
                <div class="mt-4 rounded-lg border border-flash-orange/30 bg-flash-orange/10 px-3 py-2 text-sm text-primary-900 dark:text-orange-50">
                    De app vond geen duidelijke kopjes en gebruikt daarom paginanavigatie.
                </div>
            @endif
        </section>
    @endif

    @if ($hasPdf)
        <section
            x-data="{ page: {{ $document->sections->first()?->page_number ?? 1 }} }"
            class="grid min-w-0 gap-4 xl:grid-cols-[18rem_minmax(0,1fr)]"
        >
            <aside class="min-w-0 rounded-lg border border-primary-800/10 bg-white p-3 shadow-sm dark:border-flash-orange/20 dark:bg-primary-800">
                <div class="flex flex-wrap items-start justify-between gap-3 border-b border-primary-800/10 pb-3 dark:border-flash-orange/20">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase text-flash-orange">Inhoud</p>
                        <h2 class="font-semibold text-primary-900 dark:text-white">Ga direct naar</h2>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <span class="rounded-md bg-primary-50 px-2 py-1 text-xs font-medium text-primary-900 dark:bg-primary-900 dark:text-white">
                            {{ $document->sections->count() }}
                        </span>
                        <a
                            href="{{ $pdfUrl }}"
                            target="_blank"
                            rel="noopener"
                            class="rounded-md border border-primary-800/10 px-2 py-1 text-xs font-semibold text-primary-900 transition hover:bg-primary-50 dark:border-flash-orange/20 dark:text-white dark:hover:bg-primary-900"
                        >
                            Open PDF
                        </a>
                    </div>
                </div>

                <div class="mt-3 max-h-[70vh] space-y-1 overflow-y-auto pr-1">
                    @foreach ($document->sections as $section)
                        <button
                            type="button"
                            class="grid w-full grid-cols-[minmax(0,1fr)_auto] items-start gap-3 rounded-md px-3 py-2 text-left text-sm transition hover:bg-primary-50 dark:hover:bg-primary-900"
                            x-bind:class="page === {{ $section->page_number }} ? 'bg-primary-800 text-white hover:bg-primary-800 dark:bg-flash-orange dark:text-primary-950 dark:hover:bg-flash-orange' : 'text-primary-900 dark:text-zinc-200'"
                            x-on:click="page = {{ $section->page_number }}"
                            wire:key="document-section-{{ $section->id }}"
                        >
                            <span class="u22-document-section-title" title="{{ $section->title }}">{{ $section->title }}</span>
                            <span class="shrink-0 text-xs opacity-75">p. {{ $section->page_number }}</span>
                        </button>
                    @endforeach
                </div>
            </aside>

            <team-pdf-viewer
                src="{{ $pdfUrl }}"
                page="{{ $document->sections->first()?->page_number ?? 1 }}"
                x-bind:page="page"
                x-on:team-pdf-page-changed="page = $event.detail.page"
            ></team-pdf-viewer>
        </section>
    @else
        <section class="rounded-lg border border-dashed border-primary-800/20 bg-white p-6 text-center shadow-sm dark:border-flash-orange/30 dark:bg-primary-800">
            <h2 class="font-display text-2xl font-normal leading-none text-primary-900 dark:text-white">Nog geen PDF beschikbaar</h2>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">
                @if ($isCoach)
                    Er staat nog geen document klaar voor spelers.
                @else
                    Zodra de coach het document klaarzet, kun je het hier bekijken.
                @endif
            </p>
        </section>
    @endif
</div>
