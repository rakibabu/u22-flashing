<div class="space-y-6">
    <x-page-header :title="'Check-in '.$player->name" :description="'Week van '.$checkin->week_start_date->format('d-m-Y')">
        <x-slot:actions>
            <flux:button :href="route('coach.checkins.index')" wire:navigate>Alle check-ins</flux:button>
            <flux:button :href="route('coach.players.show', $player)" wire:navigate>Bekijk speler</flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-3 md:grid-cols-4">
        <x-metric-card label="Readiness" :value="$evaluation['readiness']" :tone="$evaluation['status']" />
        <x-metric-card label="Compliance" :value="$evaluation['compliance'].'%'" />
        <x-metric-card label="Training load" :value="$checkin->calculated_training_load ?? 'n.v.t.'" />
        <x-metric-card label="Ingestuurd" :value="$checkin->submitted_at?->format('d-m H:i') ?? 'n.v.t.'" />
    </div>

    <section class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
            <div>
                <h2 class="font-semibold text-zinc-950 dark:text-white">Coachsignaal</h2>
                <p class="mt-1 text-sm text-zinc-700 dark:text-zinc-300">{{ $evaluation['reason'] }}</p>
                <p class="mt-2 text-sm font-medium text-zinc-950 dark:text-white">{{ $evaluation['next_action'] }}</p>
            </div>
            <x-status-badge :status="$evaluation['status']" />
        </div>
    </section>

    <div class="grid gap-4 lg:grid-cols-2">
        <section class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="font-semibold text-zinc-950 dark:text-white">Training</h2>
            <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                <div><dt class="text-zinc-500">Kracht</dt><dd class="font-medium">{{ $checkin->strength_sessions }}</dd></div>
                <div><dt class="text-zinc-500">Conditietrainingen / pickup</dt><dd class="font-medium">{{ $checkin->conditioning_sessions }}</dd></div>
                <div><dt class="text-zinc-500">8 min preventie/mobiliteit</dt><dd class="font-medium">{{ $checkin->mobility_sessions }}</dd></div>
                <div><dt class="text-zinc-500">Pickup maandag</dt><dd class="font-medium">{{ $checkin->pickup_monday ? 'Ja' : 'Nee' }}</dd></div>
                <div><dt class="text-zinc-500">Pickup donderdag</dt><dd class="font-medium">{{ $checkin->pickup_thursday ? 'Ja' : 'Nee' }}</dd></div>
                <div><dt class="text-zinc-500">Volledige rustdag</dt><dd class="font-medium">{{ $checkin->had_full_rest_day === null ? 'n.v.t.' : ($checkin->had_full_rest_day ? 'Ja' : 'Nee') }}</dd></div>
                <div><dt class="text-zinc-500">Minuten totaal</dt><dd class="font-medium">{{ $checkin->total_training_minutes ?? 'n.v.t.' }}</dd></div>
                <div><dt class="text-zinc-500">Hoogste RPE</dt><dd class="font-medium">{{ $checkin->highest_session_rpe ?? $checkin->rpe_highest ?? 'n.v.t.' }}</dd></div>
                <div><dt class="text-zinc-500">Training load</dt><dd class="font-medium">{{ $checkin->calculated_training_load ?? 'n.v.t.' }}</dd></div>
            </dl>
            @if ($checkin->missed_target_reason)
                <p class="mt-4 rounded-md bg-orange-50 p-3 text-sm text-orange-950 dark:bg-orange-950 dark:text-orange-100">
                    Waarom niet gelukt: {{ $checkin->missed_target_reason }}@if ($checkin->missed_target_reason_other)
                        - {{ $checkin->missed_target_reason_other }}
                    @endif
                </p>
            @endif
        </section>

        <section class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="font-semibold text-zinc-950 dark:text-white">Herstel en pijn</h2>
            <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                <div><dt class="text-zinc-500">Slaap</dt><dd class="font-medium">{{ $checkin->sleep_avg_hours ?? 'n.v.t.' }}</dd></div>
                <div><dt class="text-zinc-500">Energie</dt><dd class="font-medium">{{ $checkin->energy_score ?? 'n.v.t.' }}</dd></div>
                <div><dt class="text-zinc-500">Spierpijn</dt><dd class="font-medium">{{ $checkin->sorenessDisplay() }}</dd></div>
                <div><dt class="text-zinc-500">Pijn</dt><dd class="font-medium">{{ $checkin->pain ? 'Ja' : 'Nee' }}</dd></div>
            </dl>
            @if ($checkin->pain)
                <div class="mt-4 rounded-md bg-red-50 p-3 text-sm text-red-950 dark:bg-red-950 dark:text-red-100">
                    <p class="font-medium">{{ $checkin->pain_location ?: 'Pijnlocatie niet ingevuld' }}</p>
                    <p class="mt-1">{{ $checkin->pain_notes ?: 'Geen extra toelichting.' }}</p>
                </div>
            @endif
        </section>
    </div>

    @if ($player->isGuardDevelopment())
        <section class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="font-semibold text-zinc-950 dark:text-white">Guard development</h2>
            <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-4">
                <div><dt class="text-zinc-500">Handle sessies</dt><dd class="font-medium">{{ $checkin->handle_sessions ?? 'n.v.t.' }}</dd></div>
                <div><dt class="text-zinc-500">Handle-minuten</dt><dd class="font-medium">{{ $checkin->handle_minutes !== null ? $checkin->handle_minutes.' min' : 'n.v.t.' }}</dd></div>
                <div><dt class="text-zinc-500">Pickups</dt><dd class="font-medium">{{ $checkin->pickup_sessions ?? 'n.v.t.' }}</dd></div>
                <div><dt class="text-zinc-500">Conditieminuten</dt><dd class="font-medium">{{ $checkin->conditioning_minutes !== null ? $checkin->conditioning_minutes.' min' : 'n.v.t.' }}</dd></div>
                <div><dt class="text-zinc-500">Defence</dt><dd class="font-medium">{{ $checkin->defence_sessions ?? 'n.v.t.' }}</dd></div>
                <div><dt class="text-zinc-500">Calls</dt><dd class="font-medium">{{ $checkin->playbook_calls_learned ?? 'n.v.t.' }}</dd></div>
            </dl>
            @if ($checkin->handles_worked_on)
                <p class="mt-4 rounded-md bg-primary-50 p-3 text-sm text-primary-950 dark:bg-primary-950 dark:text-primary-100">Handles: {{ $checkin->handles_worked_on }}</p>
            @endif
            @if ($checkin->playbook_focus)
                <p class="mt-2 rounded-md bg-primary-50 p-3 text-sm text-primary-950 dark:bg-primary-950 dark:text-primary-100">Playbook: {{ $checkin->playbook_focus }}</p>
            @endif
            @if ($checkin->attendance_notes || $checkin->absence_communication_notes)
                <p class="mt-2 rounded-md bg-primary-50 p-3 text-sm text-primary-950 dark:bg-primary-950 dark:text-primary-100">
                    Aanwezigheid: {{ $checkin->attendance_notes ?: 'n.v.t.' }}
                    @if ($checkin->absence_communication_notes)
                        <br>Communicatie: {{ $checkin->absence_communication_notes }}
                    @endif
                </p>
            @endif
        </section>
    @endif

    @if ($player->tracksNutrition())
        @php
            $proteinStatusLabel = [
                'yes' => 'Ja (6-7 dagen)',
                'partial' => 'Soms (3-5 dagen)',
                'no' => 'Nee (0-2 dagen)',
            ][$checkin->protein_status] ?? 'n.v.t.';
        @endphp

        <section class="rounded-lg border border-orange-200 bg-orange-50 p-4 text-orange-950 dark:border-orange-900 dark:bg-orange-950 dark:text-orange-100">
            <h2 class="font-semibold">{{ $player->isGuardDevelopment() ? 'Voeding en lean bulk-light' : 'Voeding en bulk' }}</h2>
            <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-4">
                <div><dt class="opacity-75">Gewicht</dt><dd class="font-medium">{{ $checkin->weight_kg ?? 'n.v.t.' }}</dd></div>
                <div><dt class="opacity-75">Gem. kcal</dt><dd class="font-medium">{{ $checkin->kcal_avg ?? 'n.v.t.' }}</dd></div>
                <div><dt class="opacity-75">Eiwitstatus</dt><dd class="font-medium">{{ $proteinStatusLabel }}</dd></div>
                <div><dt class="opacity-75">Gem. eiwit</dt><dd class="font-medium">{{ $checkin->protein_avg_grams !== null ? $checkin->protein_avg_grams.'g/dag' : 'n.v.t.' }}</dd></div>
                <div><dt class="opacity-75">Eiwitdoel dagen</dt><dd class="font-medium">{{ $checkin->protein_target_days !== null ? $checkin->protein_target_days.'/7' : 'n.v.t.' }}</dd></div>
                <div><dt class="opacity-75">Eetlust</dt><dd class="font-medium">{{ $checkin->appetite_score ?? 'n.v.t.' }}</dd></div>
                <div><dt class="opacity-75">Mijn Eetmeter</dt><dd class="font-medium">{{ $checkin->used_mijn_eetmeter ? 'Ja' : 'Nee' }}</dd></div>
                <div><dt class="opacity-75">YAZIO</dt><dd class="font-medium">{{ $checkin->used_yazio ? 'Ja' : 'Nee' }}</dd></div>
            </dl>
            @if ($checkin->protein_notes)
                <p class="mt-4 rounded-md bg-white/70 p-3 text-sm text-orange-950 dark:bg-orange-900/50 dark:text-orange-100">
                    Eiwittoelichting: {{ $checkin->protein_notes }}
                </p>
            @endif
        </section>
    @else
        <section class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="font-semibold text-zinc-950 dark:text-white">Gewicht</h2>
            <p class="mt-2 text-sm text-zinc-700 dark:text-zinc-300">{{ $checkin->weight_kg ?? 'Niet ingevuld' }}</p>
        </section>
    @endif

    <section class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
        <h2 class="font-semibold text-zinc-950 dark:text-white">Opmerking speler</h2>
        <p class="mt-2 whitespace-pre-line text-sm text-zinc-700 dark:text-zinc-300">{{ $checkin->notes ?: 'Geen opmerking.' }}</p>
    </section>
</div>
