<div class="space-y-6">
    <x-page-header :title="$player->name" :description="$player->programName().' - profiel, trends en advies'">
        <x-slot:actions>
            <flux:button :href="route('coach.players.edit', $player)" wire:navigate>Bewerk</flux:button>
            <flux:button :href="route('coach.players.checkin-preview', $player)" wire:navigate>Bekijk weekcheck-scherm</flux:button>
            <flux:button wire:click="regenerateInvite">Nieuwe invite</flux:button>
        </x-slot:actions>
    </x-page-header>

    @if (session('invite_link') || $inviteLink)
        <div class="rounded-lg border border-flash-orange/30 bg-flash-orange/10 p-4 text-primary-900">
            <p class="text-sm font-medium">Invite-link</p>
            <input readonly value="{{ session('invite_link') ?? $inviteLink }}" class="mt-2 w-full rounded-md border border-flash-orange/30 bg-white px-3 py-2 text-sm">
        </div>
    @endif

    <div class="grid gap-3 md:grid-cols-4">
        <x-metric-card label="Readiness" :value="$evaluation['readiness']" :tone="$evaluation['status']" />
        <x-metric-card label="Compliance" :value="$evaluation['compliance'].'%'" />
        <x-metric-card label="Laatste gewicht" :value="$player->checkins->first()?->weight_kg ?? 'n.v.t.'" />
        <x-metric-card label="Training load" :value="$player->checkins->first()?->calculated_training_load ?? 'n.v.t.'" />
    </div>

    <section class="rounded-lg border border-primary-800/10 bg-white p-4 shadow-sm dark:border-flash-orange/20 dark:bg-primary-800">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
            <div>
            <h2 class="font-display text-2xl font-normal leading-none text-primary-900 dark:text-white">Volgende actie</h2>
                <div class="mt-2 h-1 w-10 bg-flash-orange"></div>
                <p class="mt-1 text-sm text-zinc-700 dark:text-zinc-300">{{ $evaluation['next_action'] }}</p>
                <p class="mt-2 text-sm text-zinc-500">{{ $evaluation['reason'] }}</p>
            </div>
            <x-copy-button :text="$whatsAppMessage" label="Kopieer WhatsApp-bericht" />
        </div>
        <input readonly value="{{ $whatsAppMessage }}" class="mt-3 w-full rounded-md border border-primary-800/10 bg-primary-50 px-3 py-2 text-sm dark:border-flash-orange/20 dark:bg-primary-900">
    </section>

    @if ($bulk)
        <section class="rounded-lg border border-flash-orange/30 bg-flash-orange/10 p-4 text-primary-900 dark:border-flash-orange/40 dark:bg-flash-orange/15 dark:text-orange-50">
            <h2 class="font-display text-2xl font-normal leading-none">Bulk-dashboard</h2>
            <div class="mt-2 h-1 w-10 bg-flash-orange"></div>
            <div class="mt-3 grid gap-3 sm:grid-cols-4">
                <x-metric-card label="Huidig gewicht" :value="$bulk['current_weight'] ?? 'n.v.t.'" />
                <x-metric-card label="Gewichtstrend" :value="$bulk['weight_trend'] === null ? 'n.v.t.' : number_format($bulk['weight_trend'], 1).' kg/w'" />
                <x-metric-card label="17 aug doel" :value="$bulk['target_weight'] ? number_format($bulk['target_weight'], 0).' kg' : '66-68 kg'" />
                <x-metric-card label="Stretchdoel" :value="$bulk['stretch_target']" />
                <x-metric-card label="Gem. kcal" :value="$bulk['kcal_avg'] ?? 'n.v.t.'" />
                <x-metric-card label="Kcal gym/pickup" :value="($bulk['kcal_training_day'] ?? 'n.v.t.').'/'.($bulk['kcal_pickup_day'] ?? 'n.v.t.')" />
                <x-metric-card label="Eiwitstatus" :value="$bulk['protein_status'] ?? 'n.v.t.'" />
                <x-metric-card label="Gem. eiwit" :value="$bulk['protein_avg_grams'] !== null ? $bulk['protein_avg_grams'].'g/dag' : 'n.v.t.'" />
                <x-metric-card label="Eiwitdagen" :value="$bulk['protein_target_days'] !== null ? $bulk['protein_target_days'].'/7' : 'n.v.t.'" />
                <x-metric-card label="Eiwitdoel" :value="$bulk['protein_target']" />
                <x-metric-card label="Kracht" :value="$bulk['strength_sessions'] ?? 'n.v.t.'" />
                <x-metric-card label="Pickup maandag" :value="$bulk['pickup_monday'] ? 'ja' : 'nee'" />
                <x-metric-card label="Eetlust" :value="$bulk['appetite_score'] ?? 'n.v.t.'" />
                <x-metric-card label="Seizoen/lang" :value="$bulk['season_target'].' / '.$bulk['long_term_target'].' kg'" />
            </div>
            @if ($bulk['protein_notes'])
                <p class="mt-3 rounded-md bg-white/70 p-3 text-sm dark:bg-primary-900/60">Eiwittoelichting: {{ $bulk['protein_notes'] }}</p>
            @endif
            <p class="mt-3 text-sm">{{ $bulk['kcal_advice'] }}</p>
            @if ($player->settings?->notes)
                <p class="mt-2 text-sm opacity-85">{{ $player->settings->notes }}</p>
            @endif
        </section>
    @endif

    <div class="grid gap-4 lg:grid-cols-2">
        <section class="rounded-lg border border-primary-800/10 bg-white p-4 shadow-sm dark:border-flash-orange/20 dark:bg-primary-800">
            <h2 class="font-display text-2xl font-normal leading-none text-primary-900 dark:text-white">Laatste check-ins</h2>
            <div class="mt-2 h-1 w-10 bg-flash-orange"></div>
            <div class="mt-3 space-y-2">
                @foreach ($player->checkins as $checkin)
                    <div class="rounded-md bg-primary-50 p-3 text-sm dark:bg-primary-900" wire:key="checkin-{{ $checkin->id }}">
                        <p class="font-medium">{{ $checkin->week_start_date->format('d-m-Y') }} - {{ $checkin->strength_sessions }} kracht, {{ $checkin->conditioning_sessions }} conditie, {{ $checkin->mobility_sessions }} mobiliteit</p>
                        <p class="text-zinc-600 dark:text-zinc-300">Slaap {{ $checkin->sleep_avg_hours ?? 'n.v.t.' }} uur, energie {{ $checkin->energy_score ?? 'n.v.t.' }}, pijn {{ $checkin->pain ? 'ja' : 'nee' }}, load {{ $checkin->calculated_training_load ?? 'n.v.t.' }}</p>
                        @if ($player->isMuscleGain())
                            <p class="text-zinc-600 dark:text-zinc-300">Eiwit {{ ['yes' => 'ja (6-7 dagen)', 'partial' => 'soms (3-5 dagen)', 'no' => 'nee (0-2 dagen)'][$checkin->protein_status] ?? 'n.v.t.' }}@if ($checkin->protein_avg_grams !== null)
                                    , {{ $checkin->protein_avg_grams }}g/dag
                                @endif@if ($checkin->protein_target_days !== null)
                                    , {{ $checkin->protein_target_days }}/7 dagen
                                @endif</p>
                        @endif
                        @if ($checkin->missed_target_reason)
                            <p class="mt-1 text-zinc-500">
                                Waarom niet gelukt: {{ $checkin->missed_target_reason }}@if ($checkin->missed_target_reason_other)
                                    - {{ $checkin->missed_target_reason_other }}
                                @endif
                            </p>
                        @endif
                        <flux:button size="sm" class="mt-3" :href="route('coach.checkins.show', $checkin)" wire:navigate>Bekijk volledige check-in</flux:button>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="rounded-lg border border-primary-800/10 bg-white p-4 shadow-sm dark:border-flash-orange/20 dark:bg-primary-800">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="font-display text-2xl font-normal leading-none text-primary-900 dark:text-white">Advies maken</h2>
                    <div class="mt-2 h-1 w-10 bg-flash-orange"></div>
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">
                        Week {{ $selectedAdviceWeekNumber }} - {{ $selectedAdviceWeekRange }}
                    </p>
                    @if ($selectedAdviceCheckin)
                        <p class="mt-1 text-xs text-emerald-700 dark:text-emerald-300">Gebaseerd op de ingediende weekcheck van deze week.</p>
                    @else
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Geen ingediende weekcheck voor deze adviesweek.</p>
                    @endif
                </div>
                <div class="flex flex-col gap-2 sm:min-w-56">
                    <div class="flex flex-wrap gap-2 sm:justify-end">
                        <flux:button size="sm" icon="chevron-left" wire:click="previousAdviceWeek">Vorige</flux:button>
                        <flux:button size="sm" icon="calendar-days" wire:click="currentAdviceWeek">Deze week</flux:button>
                        <flux:button size="sm" icon="chevron-right" wire:click="nextAdviceWeek" :disabled="$isCurrentAdviceWeek">Volgende</flux:button>
                    </div>
                    <flux:field>
                        <flux:label>Adviesweek</flux:label>
                        <flux:input type="week" wire:model.live="adviceWeek" max="{{ $currentAdviceWeekValue }}" />
                    </flux:field>
                </div>
            </div>
            <flux:textarea wire:model="adviceBody" rows="7" class="mt-3" />
            <label class="mt-3 flex items-center gap-2 text-sm">
                <input type="checkbox" wire:model="visibleToPlayer" class="rounded border-zinc-300">
                Zichtbaar maken voor speler
            </label>
            <div class="mt-3 flex gap-2">
                <flux:button wire:click="saveAdvice" variant="primary">Opslaan</flux:button>
                <input readonly value="{{ $adviceBody }}" class="min-w-0 flex-1 rounded-md border border-primary-800/10 px-3 py-2 text-sm dark:border-flash-orange/20 dark:bg-primary-900">
            </div>
        </section>
    </div>

    <section class="space-y-3">
        <h2 class="font-semibold">Coachnotities</h2>
        @foreach ($player->coachNotes as $note)
            <div wire:key="note-{{ $note->id }}">
                @if ($editingNoteId === $note->id)
                    <article class="rounded-lg border border-primary-800/10 bg-white p-4 shadow-sm dark:border-flash-orange/20 dark:bg-primary-800">
                        <form wire:submit="updateAdvice" class="space-y-3">
                            <flux:input wire:model="editingNoteTitle" label="Titel" />
                            <flux:error name="editingNoteTitle" />

                            <flux:textarea wire:model="editingNoteBody" label="Advies" rows="5" />
                            <flux:error name="editingNoteBody" />

                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" wire:model="editingNoteVisibleToPlayer" class="rounded border-zinc-300">
                                Zichtbaar voor speler
                            </label>

                            <div class="flex flex-wrap gap-2">
                                <flux:button type="submit" size="sm" variant="primary" icon="check">Opslaan</flux:button>
                                <flux:button type="button" size="sm" wire:click="cancelAdviceEdit">Annuleer</flux:button>
                            </div>
                        </form>
                    </article>
                @else
                    <x-advice-card :note="$note" />
                    <div class="mt-2 flex flex-wrap gap-2">
                        <flux:button size="sm" icon="pencil-square" wire:click="editAdvice({{ $note->id }})">Bewerk</flux:button>
                        <flux:button size="sm" variant="danger" icon="trash" wire:click="deleteAdvice({{ $note->id }})" wire:confirm="Weet je zeker dat je dit advies wilt verwijderen?">Verwijder</flux:button>
                    </div>
                @endif
            </div>
        @endforeach
    </section>

    <section class="rounded-lg border border-primary-800/10 bg-white p-4 shadow-sm dark:border-flash-orange/20 dark:bg-primary-800">
        <h2 class="font-display text-2xl font-normal leading-none text-primary-900 dark:text-white">Speler-tijdlijn</h2>
        <div class="mt-2 h-1 w-10 bg-flash-orange"></div>
        <div class="mt-4 space-y-3">
            @foreach ($timeline as $item)
                <article class="rounded-md border border-zinc-200 p-3 text-sm dark:border-zinc-800" wire:key="timeline-{{ $loop->index }}">
                    <div class="flex flex-wrap items-center gap-2">
                        <x-status-badge :status="$item['tone'] === 'neutral' ? 'default' : $item['tone']" />
                        <span class="font-medium">{{ $item['type'] }}</span>
                        <span class="text-zinc-500">{{ is_string($item['date']) ? $item['date'] : $item['date']?->format('d-m-Y') }}</span>
                    </div>
                    <h3 class="mt-2 font-semibold text-zinc-950 dark:text-white">{{ $item['title'] }}</h3>
                    <p class="mt-1 text-zinc-700 dark:text-zinc-300">{{ $item['body'] }}</p>
                </article>
            @endforeach
        </div>
    </section>
</div>
