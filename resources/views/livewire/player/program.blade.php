<div class="space-y-6">
    <x-page-header title="Programma" :description="$template?->name ?? $player->programName()" />

    <section class="grid gap-3 md:grid-cols-4">
        <x-metric-card label="Kracht" :value="($player->settings?->strength_target_per_week ?? 0).'x per week'" />
        <x-metric-card label="Conditie/pickup" :value="($player->settings?->conditioning_target_per_week ?? 0).'x per week'" />
        <x-metric-card label="Preventie" :value="($player->settings?->mobility_target_per_week ?? 0).'x per week'" />
        <x-metric-card label="Rust" value="1 volledige dag" />
        @if ($player->isGuardDevelopment())
            <x-metric-card label="Handles" :value="($player->settings?->handle_minutes_target_per_week ?? 75).' min per week'" />
            <x-metric-card label="Pickups" :value="($player->settings?->pickup_target_per_week ?? 1).'x per week'" />
            <x-metric-card label="Defence" :value="($player->settings?->defence_sessions_target_per_week ?? 2).'x per week'" />
            <x-metric-card label="Playbook" :value="($player->settings?->playbook_calls_target_per_week ?? 1).' call per week'" />
        @endif
    </section>

    @if ($player->isMuscleGain())
        <section class="rounded-lg border border-flash-orange/30 bg-flash-orange/10 p-5 text-primary-900 dark:bg-flash-orange/15 dark:text-orange-50">
            <div class="grid gap-4 lg:grid-cols-[1.3fr_1fr]">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-normal text-flash-orange">Persoonlijk spiermassa-plan</p>
                    <h2 class="mt-1 font-display text-2xl font-normal leading-none">Massa bouwen zonder snelheid en belastbaarheid te verliezen</h2>
                    <p class="mt-2 text-sm">Maandagpickup telt als basketbalconditie. Donderdagpickup is optioneel als je meedoet. De basis is 3 vaste gymmomenten, korte slimme conditie en dagelijks genoeg kcal/eiwit.</p>
                </div>
                <dl class="grid gap-2 text-sm sm:grid-cols-2">
                    <div class="rounded-md bg-white/75 p-3 dark:bg-primary-900/60">
                        <dt class="font-medium">17 augustus</dt>
                        <dd>66-68 kg goed, 68-70 kg stretch</dd>
                    </div>
                    <div class="rounded-md bg-white/75 p-3 dark:bg-primary-900/60">
                        <dt class="font-medium">Kcal</dt>
                        <dd>3000 min, 3300-3400 gym, 3600 pickup</dd>
                    </div>
                    <div class="rounded-md bg-white/75 p-3 dark:bg-primary-900/60">
                        <dt class="font-medium">Eiwit</dt>
                        <dd>120-130g per dag</dd>
                    </div>
                    <div class="rounded-md bg-white/75 p-3 dark:bg-primary-900/60">
                        <dt class="font-medium">Tracking</dt>
                        <dd>Mijn Eetmeter, YAZIO als backup</dd>
                    </div>
                </dl>
            </div>
            <p class="mt-4 text-sm">Weeg 3x per week in de ochtend en kijk naar het weekgemiddelde. Als dat 2 weken niet stijgt: +250 kcal per dag.</p>
        </section>
    @endif

    @if ($player->isGuardDevelopment())
        <section class="rounded-lg border border-flash-orange/30 bg-flash-orange/10 p-5 text-primary-900 dark:bg-flash-orange/15 dark:text-orange-50">
            <div class="grid gap-4 lg:grid-cols-[1.3fr_1fr]">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-normal text-flash-orange">Guard development</p>
                    <h2 class="mt-1 font-display text-2xl font-normal leading-none">Richting betrouwbare 1 groeien</h2>
                    <p class="mt-2 text-sm">Elke week zichtbaar werk leveren: aanwezig zijn, eerlijk communiceren, handles/passing onder druk, defence first-step, playbook/calls en conditie/kracht.</p>
                </div>
                <dl class="grid gap-2 text-sm sm:grid-cols-2">
                    <div class="rounded-md bg-white/75 p-3 dark:bg-primary-900/60">
                        <dt class="font-medium">Skills</dt>
                        <dd>3x 25-35 min handles/passing</dd>
                    </div>
                    <div class="rounded-md bg-white/75 p-3 dark:bg-primary-900/60">
                        <dt class="font-medium">Fysiek</dt>
                        <dd>2x kracht, 2x conditie/pickup</dd>
                    </div>
                    <div class="rounded-md bg-white/75 p-3 dark:bg-primary-900/60">
                        <dt class="font-medium">Defence</dt>
                        <dd>2x first-step/no-middle werk</dd>
                    </div>
                    <div class="rounded-md bg-white/75 p-3 dark:bg-primary-900/60">
                        <dt class="font-medium">Voeding</dt>
                        <dd>Gewicht, kcal en eiwit wekelijks meten</dd>
                    </div>
                </dl>
            </div>
        </section>
    @endif

    @if ($template)
        <x-program-card :title="$template->goal" :body="$template->description" />
        <div class="grid gap-3 md:grid-cols-2">
            @foreach ($template->phases as $phase)
                <x-program-card :title="$phase->name" :body="$phase->description" wire:key="phase-{{ $phase->id }}" />
            @endforeach
        </div>
    @endif

    @if ($hasTrainingProgramPdf)
        <section class="space-y-3">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-lg font-semibold">Jouw persoonlijke trainingsprogramma</h2>
                <flux:button size="sm" :href="route('player.program.pdf')" target="_blank">Open PDF</flux:button>
            </div>
            <iframe
                src="{{ route('player.program.pdf') }}"
                title="Persoonlijk trainingsprogramma"
                class="h-[75vh] w-full rounded-lg border border-zinc-200 bg-white dark:border-zinc-800"
            ></iframe>
        </section>
    @endif

    @if ($exercises->isNotEmpty())
        <section class="space-y-4">
            <h2 class="text-lg font-semibold">Oefenbibliotheek</h2>
            @foreach ($exercises as $category => $items)
                <div class="space-y-2" wire:key="category-{{ $category }}">
                    <h3 class="font-medium text-orange-600">{{ $category }}</h3>
                    <div class="grid gap-3 md:grid-cols-2">
                        @foreach ($items as $item)
                            <x-program-card :title="$item->name" :body="$item->description.' '.$item->execution.' Cue: '.$item->coaching_cues" wire:key="exercise-{{ $item->id }}" />
                        @endforeach
                    </div>
                </div>
            @endforeach
        </section>
    @endif
</div>
