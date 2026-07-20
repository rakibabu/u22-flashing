<div class="space-y-6">
    <x-page-header :title="$document->title" :description="$document->description" />

    @if ($document->type === \App\Models\TeamDocument::Playbook)
        @can('manage-coach-area')
            <section class="rounded-lg border border-primary-800/10 bg-white p-4 shadow-sm dark:border-flash-orange/20 dark:bg-primary-800">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase text-flash-orange">Interactief playbook</p>
                        <h2 class="mt-1 font-display text-2xl font-normal leading-none text-primary-900 dark:text-white">
                            BasketballTrainer
                        </h2>

                        @if ($basketballTrainerLink)
                            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">
                                Gekoppeld aan <span class="font-semibold text-primary-900 dark:text-white">{{ $basketballTrainerLink->external_title }}</span>
                                @if (filled($basketballTrainerLink->metadata['season'] ?? null))
                                    · {{ $basketballTrainerLink->metadata['season'] }}
                                @endif
                                · {{ $basketballTrainerLink->metadata['plays_count'] ?? 0 }} plays
                            </p>
                            @if ($basketballTrainerLink->last_checked_at)
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                    Laatst gecontroleerd {{ $basketballTrainerLink->last_checked_at->format('d-m-Y H:i') }}
                                </p>
                            @endif
                        @else
                            <p class="mt-2 max-w-2xl text-sm text-zinc-600 dark:text-zinc-300">
                                Koppel het live playbook voor animaties en stap-voor-stap uitleg. De PDF blijft beschikbaar als back-up.
                            </p>
                        @endif
                    </div>

                    <div class="flex shrink-0 flex-wrap gap-2">
                        @if ($basketballTrainerLink)
                            <flux:button
                                size="sm"
                                icon="arrow-path"
                                wire:click="refreshBasketballTrainerPlaybook"
                                wire:loading.attr="disabled"
                                wire:target="refreshBasketballTrainerPlaybook"
                            >
                                Vernieuwen
                            </flux:button>
                            <flux:button size="sm" icon="link" wire:click="openBasketballTrainerModal">
                                Ander playbook
                            </flux:button>
                            <flux:button
                                size="sm"
                                variant="danger"
                                icon="x-mark"
                                wire:click="unlinkBasketballTrainerPlaybook"
                                wire:confirm="Weet je zeker dat je dit BasketballTrainer-playbook wilt ontkoppelen? De PDF blijft gewoon beschikbaar."
                            >
                                Ontkoppelen
                            </flux:button>
                        @else
                            <flux:button variant="primary" icon="link" wire:click="openBasketballTrainerModal">
                                Playbook koppelen
                            </flux:button>
                        @endif
                    </div>
                </div>

                @if ($basketballTrainerLink && filled($basketballTrainerLink->metadata['edit_url'] ?? null))
                    <a
                        href="{{ $basketballTrainerLink->metadata['edit_url'] }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="mt-3 inline-flex text-sm font-semibold text-primary-700 underline decoration-flash-orange/50 underline-offset-4 hover:text-flash-orange dark:text-orange-100"
                    >
                        Bewerk in BasketballTrainer
                    </a>
                @endif
            </section>
        @endcan

        @if ($basketballTrainerError)
            <flux:callout
                variant="warning"
                icon="exclamation-triangle"
                heading="Interactief playbook tijdelijk niet beschikbaar"
                text="{{ $basketballTrainerError }} De PDF-back-up hieronder blijft bruikbaar."
            />
        @endif

        @if ($basketballTrainerLink)
            <section class="overflow-hidden rounded-lg border border-primary-800/10 bg-white shadow-sm dark:border-flash-orange/20 dark:bg-primary-800">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-primary-800/10 px-4 py-3 dark:border-flash-orange/20">
                    <div>
                        <p class="text-xs font-semibold uppercase text-flash-orange">Live vanuit BasketballTrainer</p>
                        <h2 class="font-semibold text-primary-900 dark:text-white">{{ $basketballTrainerLink->external_title }}</h2>
                    </div>

                    @if (! $basketballTrainerEmbedUrl)
                        <flux:button
                            size="sm"
                            icon="arrow-path"
                            wire:click="reloadBasketballTrainerViewer"
                            wire:loading.attr="disabled"
                            wire:target="reloadBasketballTrainerViewer"
                        >
                            Opnieuw proberen
                        </flux:button>
                    @endif
                </div>

                @if ($basketballTrainerEmbedUrl)
                    <basketball-trainer-embed class="block min-h-[42rem] bg-primary-950/5 dark:bg-primary-950">
                        <div data-embed-loading class="flex min-h-[42rem] items-center justify-center px-6 text-center text-sm text-zinc-500 dark:text-zinc-400">
                            Interactief playbook laden…
                        </div>
                        <iframe
                            data-embed-frame
                            src="{{ $basketballTrainerEmbedUrl }}"
                            title="Interactief playbook {{ $basketballTrainerLink->external_title }}"
                            class="hidden min-h-[42rem] w-full border-0"
                            sandbox="allow-scripts allow-same-origin"
                            allow="fullscreen"
                            allowfullscreen
                            loading="eager"
                            referrerpolicy="no-referrer"
                        ></iframe>
                    </basketball-trainer-embed>
                @else
                    <div class="flex min-h-64 items-center justify-center px-6 py-12 text-center">
                        <div class="max-w-md">
                            <h3 class="font-semibold text-primary-900 dark:text-white">Viewer kan nu niet worden geladen</h3>
                            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">
                                Probeer opnieuw of gebruik de PDF-back-up hieronder.
                            </p>
                        </div>
                    </div>
                @endif
            </section>
        @endif
    @endif

    @can('manage-coach-area')
        <section class="rounded-lg border border-primary-800/10 bg-white p-4 shadow-sm dark:border-flash-orange/20 dark:bg-primary-800">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase text-primary-700 dark:text-orange-100">Coach upload</p>
                    <h2 class="mt-1 font-display text-2xl font-normal leading-none text-primary-900 dark:text-white">
                        {{ $document->type === \App\Models\TeamDocument::Playbook ? 'PDF-back-up vervangen' : 'PDF vervangen' }}
                    </h2>
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
    @endcan

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
    @elseif (! $basketballTrainerLink)
        <section class="rounded-lg border border-dashed border-primary-800/20 bg-white p-6 text-center shadow-sm dark:border-flash-orange/30 dark:bg-primary-800">
            <h2 class="font-display text-2xl font-normal leading-none text-primary-900 dark:text-white">Nog geen PDF beschikbaar</h2>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">
                @can('manage-coach-area')
                    Er staat nog geen document klaar voor spelers.
                @else
                    Zodra de coach het document klaarzet, kun je het hier bekijken.
                @endcan
            </p>
        </section>
    @endif

    @if ($document->type === \App\Models\TeamDocument::Playbook)
        <flux:modal wire:model="showBasketballTrainerModal" class="w-full md:max-w-xl">
            <form wire:submit="linkBasketballTrainerPlaybook" class="space-y-5">
                <div>
                    <flux:heading size="lg">BasketballTrainer-playbook koppelen</flux:heading>
                    <flux:text class="mt-2">
                        Kies het playbook dat spelers hier met animaties en uitleg mogen bekijken.
                    </flux:text>
                </div>

                <flux:select wire:model="selectedBasketballTrainerPlaybook" label="Playbook">
                    <flux:select.option value="">Kies een playbook</flux:select.option>
                    @foreach ($availableBasketballTrainerPlaybooks as $playbook)
                        <flux:select.option value="{{ $playbook['id'] }}" wire:key="basketball-trainer-option-{{ $playbook['id'] }}">
                            {{ $playbook['title'] }}@if ($playbook['season']) · {{ $playbook['season'] }}@endif · {{ $playbook['plays_count'] }} plays
                        </flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="selectedBasketballTrainerPlaybook" />

                @if ($availableBasketballTrainerPlaybooks === [])
                    <p class="text-sm text-zinc-600 dark:text-zinc-300">
                        Er zijn nog geen toegankelijke playbooks gevonden in BasketballTrainer.
                    </p>
                @endif

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button type="button">Annuleren</flux:button>
                    </flux:modal.close>
                    <flux:button
                        type="submit"
                        variant="primary"
                        wire:loading.attr="disabled"
                        wire:target="linkBasketballTrainerPlaybook"
                        :disabled="$availableBasketballTrainerPlaybooks === []"
                    >
                        Koppelen
                    </flux:button>
                </div>
            </form>
        </flux:modal>
    @endif
</div>
