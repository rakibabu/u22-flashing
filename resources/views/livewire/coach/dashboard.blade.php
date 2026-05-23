<div class="space-y-6">
    <x-page-header title="Coach dashboard" description="Flashing Heiloo U22 zomerprogramma 11 mei t/m 16 augustus, start trainingen 17 augustus.">
        <x-slot:actions>
            <flux:button :href="route('coach.players.create')" variant="primary" wire:navigate>Speler toevoegen</flux:button>
            <flux:button :href="route('coach.analysis-export')" wire:navigate>Analyse export</flux:button>
        </x-slot:actions>
    </x-page-header>

    <section class="overflow-hidden rounded-lg border border-primary-800/10 bg-white shadow-sm dark:border-flash-orange/20 dark:bg-primary-800">
        <div class="border-b border-flash-orange/30 bg-primary-800 px-4 py-3 text-white">
            <h2 class="font-display text-2xl font-normal leading-none">Vandaag bijsturen</h2>
            <div class="mt-2 h-1 w-10 bg-flash-orange"></div>
            <p class="mt-2 text-sm text-white/75">Eén duidelijke actie per speler, klaar om te kopiëren of op te volgen.</p>
        </div>
        <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
            @foreach ($actionRows as $row)
                <article class="grid gap-3 px-4 py-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)_auto]" wire:key="action-row-{{ $row['player']->id }}">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <x-status-badge :status="$row['status']" />
                            @if ($row['followed_up'])
                                <span class="rounded-md bg-zinc-100 px-2 py-1 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">Opgevolgd</span>
                            @endif
                        </div>
                        <h3 class="font-semibold text-primary-900 dark:text-white">{{ $row['player']->name }}</h3>
                        <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ $row['reason'] }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase text-flash-orange">Volgende actie</p>
                        <p class="mt-1 text-sm text-zinc-800 dark:text-zinc-200">{{ $row['next_action'] }}</p>
                    </div>
                    <div class="flex flex-wrap items-start gap-2 lg:justify-end">
                        <flux:button size="sm" :href="route('coach.players.show', $row['player'])" wire:navigate>Bekijk speler</flux:button>
                        <flux:button size="sm" wire:click="generateAdvice({{ $row['player']->id }})">Genereer advies</flux:button>
                        <x-copy-button size="sm" :text="$row['whatsapp']" label="Kopieer WhatsApp" />
                        <flux:button size="sm" wire:click="markFollowedUp({{ $row['player']->id }})">{{ $row['followed_up'] ? 'Opgevolgd' : 'Markeer opgevolgd' }}</flux:button>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <x-metric-card label="Actieve spelers" :value="$activePlayers" />
        <x-metric-card label="Check-ins deze week" :value="$checkinsThisWeek" tone="green" />
        <x-metric-card label="Niet ingevuld" :value="$missingThisWeek" tone="orange" />
        <x-metric-card label="Rode signalen" :value="$redSignals" tone="red" />
        <x-metric-card label="Oranje signalen" :value="$orangeSignals" tone="orange" />
        <x-metric-card label="Blessuremeldingen" :value="$painSignals" tone="red" />
        <x-metric-card label="Gem. compliance" :value="$avgCompliance.'%'" />
        <x-metric-card label="Bulk trend" :value="$rows->firstWhere('player.program_type', 'muscle_gain')['checkin']->weight_kg ?? 'n.v.t.'" />
    </div>

    <div class="grid gap-4 xl:grid-cols-2">
        <section class="rounded-lg border border-flash-orange/30 bg-flash-orange/10 p-4 text-primary-900 dark:border-flash-orange/40 dark:bg-flash-orange/15 dark:text-orange-50">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="font-semibold">Check-ins ontbreken</h2>
                    <p class="mt-1 text-sm opacity-80">{{ $missingPlayers->count() }} speler(s) hebben deze week nog niet ingevuld.</p>
                </div>
                @if ($missingPlayers->isNotEmpty())
                    <x-copy-button size="sm" :text="$groupReminder" label="Kopieer reminder voor deze spelers" />
                @endif
            </div>
            @if ($missingPlayers->isNotEmpty())
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach ($missingPlayers as $player)
                        <span class="rounded-md bg-white px-2 py-1 text-sm text-primary-900 dark:bg-primary-900 dark:text-orange-100" wire:key="missing-{{ $player->id }}">{{ $player->name }}</span>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="rounded-lg border border-primary-800/10 bg-white p-4 shadow-sm dark:border-flash-orange/20 dark:bg-primary-800">
            <h2 class="font-display text-2xl font-normal leading-none text-primary-900 dark:text-white">Bulk-dashboard</h2>
            <div class="mt-2 h-1 w-10 bg-flash-orange"></div>
            <div class="mt-3 space-y-3">
                @forelse ($bulkRows as $row)
                    <div class="rounded-md bg-primary-50 p-3 text-sm dark:bg-primary-900" wire:key="bulk-{{ $row['player']->id }}">
                        <div class="flex items-center justify-between gap-3">
                            <p class="font-medium text-primary-900 dark:text-white">{{ $row['player']->name }}</p>
                            <x-status-badge :status="$row['status']" />
                        </div>
                        <div class="mt-2 grid gap-2 sm:grid-cols-4">
                            <span>Gewicht: {{ $row['bulk']['current_weight'] ?? 'n.v.t.' }}</span>
                            <span>Trend: {{ $row['bulk']['weight_trend'] === null ? 'n.v.t.' : number_format($row['bulk']['weight_trend'], 1).' kg/w' }}</span>
                            <span>Kcal: {{ $row['bulk']['kcal_avg'] ?? 'n.v.t.' }}</span>
                            <span>Eiwit: {{ $row['bulk']['protein_status'] ?? 'n.v.t.' }}</span>
                            <span>Gram eiwit: {{ $row['bulk']['protein_avg_grams'] !== null ? $row['bulk']['protein_avg_grams'].'g/dag' : 'n.v.t.' }}</span>
                            <span>Dagen eiwit: {{ $row['bulk']['protein_target_days'] !== null ? $row['bulk']['protein_target_days'].'/7' : 'n.v.t.' }}</span>
                            <span>Kracht: {{ $row['bulk']['strength_sessions'] ?? 'n.v.t.' }}</span>
                            <span>Pickup ma: {{ $row['bulk']['pickup_monday'] ? 'ja' : 'nee' }}</span>
                            <span>Eetlust: {{ $row['bulk']['appetite_score'] ?? 'n.v.t.' }}</span>
                        </div>
                        @if ($row['bulk']['protein_notes'])
                            <p class="mt-2 text-zinc-700 dark:text-zinc-300">Eiwit: {{ $row['bulk']['protein_notes'] }}</p>
                        @endif
                        <p class="mt-2 text-zinc-700 dark:text-zinc-300">{{ $row['bulk']['kcal_advice'] }}</p>
                    </div>
                @empty
                    <p class="text-sm text-zinc-600 dark:text-zinc-300">Geen bulk-spelers actief.</p>
                @endforelse
            </div>
        </section>
    </div>

    <div class="grid gap-3 md:grid-cols-3">
        <flux:input wire:model.live="search" placeholder="Zoek speler" />
        <flux:select wire:model.live="program" placeholder="Alle programma's">
            <flux:select.option value="">Alle programma's</flux:select.option>
            <flux:select.option value="conditioning">Conditie</flux:select.option>
            <flux:select.option value="muscle_gain">Bulk/kracht</flux:select.option>
            <flux:select.option value="maintenance">Onderhoud</flux:select.option>
        </flux:select>
        <flux:select wire:model.live="status" placeholder="Alle statussen">
            <flux:select.option value="">Alle statussen</flux:select.option>
            <flux:select.option value="green">Groen</flux:select.option>
            <flux:select.option value="orange">Oranje</flux:select.option>
            <flux:select.option value="red">Rood</flux:select.option>
        </flux:select>
    </div>

    <div class="overflow-hidden rounded-lg border border-primary-800/10 bg-white shadow-sm dark:border-flash-orange/20 dark:bg-primary-800">
        <div class="border-b border-primary-800/10 px-4 py-3 dark:border-flash-orange/20">
            <h2 class="font-display text-2xl font-normal leading-none text-primary-900 dark:text-white">Deze week bijsturen</h2>
            <div class="mt-2 h-1 w-10 bg-flash-orange"></div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                <thead class="bg-primary-800 text-left text-xs uppercase text-white/80">
                    <tr>
                        <th class="px-4 py-3">Speler</th>
                        <th class="px-4 py-3">Programma</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Reden</th>
                        <th class="px-4 py-3">Gewicht</th>
                        <th class="px-4 py-3">Compliance</th>
                        <th class="px-4 py-3">Energie</th>
                        <th class="px-4 py-3">Pijn</th>
                        <th class="px-4 py-3">Rustdag</th>
                        <th class="px-4 py-3">Training load</th>
                        <th class="px-4 py-3">Next action</th>
                        <th class="px-4 py-3">Acties</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($rows as $row)
                        <tr wire:key="dashboard-player-{{ $row['player']->id }}">
                            <td class="px-4 py-3 font-medium">{{ $row['player']->name }}</td>
                            <td class="px-4 py-3">{{ $row['player']->programName() }}</td>
                            <td class="px-4 py-3"><x-status-badge :status="$row['status']" /></td>
                            <td class="px-4 py-3">{{ $row['reason'] }}</td>
                            <td class="px-4 py-3">{{ $row['checkin']?->weight_kg ?? 'n.v.t.' }}</td>
                            <td class="px-4 py-3">{{ $row['compliance'] }}%</td>
                            <td class="px-4 py-3">{{ $row['checkin']?->energy_score ?? 'n.v.t.' }}</td>
                            <td class="px-4 py-3">{{ $row['checkin']?->pain ? 'Ja' : 'Nee' }}</td>
                            <td class="px-4 py-3">{{ $row['checkin']?->had_full_rest_day === null ? 'n.v.t.' : ($row['checkin']->had_full_rest_day ? 'Ja' : 'Nee') }}</td>
                            <td class="px-4 py-3">{{ $row['checkin']?->calculated_training_load ?? 'n.v.t.' }}</td>
                            <td class="max-w-md px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $row['next_action'] }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-2">
                                    <flux:button size="sm" :href="route('coach.players.show', $row['player'])" wire:navigate>Bekijk</flux:button>
                                    <flux:button size="sm" wire:click="generateAdvice({{ $row['player']->id }})">Advies</flux:button>
                                    <x-copy-button size="sm" :text="$row['whatsapp']" label="WhatsApp" />
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
