<div class="mx-auto max-w-2xl space-y-6">
    <x-page-header title="Weekcheck preview" :description="$player->name.' - '.$player->programName()">
        <x-slot:actions>
            <flux:button :href="route('coach.players.show', $player)" wire:navigate>Terug naar speler</flux:button>
        </x-slot:actions>
    </x-page-header>

    <section class="rounded-lg border border-orange-200 bg-orange-50 p-4 text-orange-950 dark:border-orange-900 dark:bg-orange-950 dark:text-orange-100">
        <h2 class="font-semibold">Coach preview</h2>
        <p class="mt-1 text-sm">Dit is het weekcheck-scherm voor deze speler. Je kunt velden aanklikken om conditionele vragen te zien; er wordt niets opgeslagen.</p>
    </section>

    <section class="u22-target-summary">
        <div class="flex items-center justify-between gap-3">
            <p class="text-xs font-semibold uppercase text-primary-800">Targets deze week</p>
            <p class="text-xs text-zinc-600">Aantal keer</p>
        </div>

        <div class="grid grid-cols-3 gap-2">
            <div class="u22-target-chip">
                <span>Kracht</span>
                <strong>{{ $player->settings?->strength_target_per_week ? $player->settings->strength_target_per_week.'x' : 'n.v.t.' }}</strong>
            </div>

            <div class="u22-target-chip">
                <span>Conditie</span>
                <strong>{{ $player->settings?->conditioning_target_per_week ? $player->settings->conditioning_target_per_week.'x' : 'n.v.t.' }}</strong>
            </div>

            <div class="u22-target-chip">
                <span>Mobiliteit</span>
                <strong>{{ $player->settings?->mobility_target_per_week ? $player->settings->mobility_target_per_week.'x' : 'n.v.t.' }}</strong>
            </div>
        </div>
    </section>

    @if ($player->isMuscleGain())
        <section class="u22-target-summary">
            <div class="flex items-center justify-between gap-3">
                <p class="text-xs font-semibold uppercase text-primary-800">Voeding targets</p>
                <p class="text-xs text-zinc-600">Spiermassa</p>
            </div>

            <div class="grid grid-cols-3 gap-2">
                <div class="u22-target-chip">
                    <span>Min kcal</span>
                    <strong>{{ $player->settings?->kcal_minimum ?? 'n.v.t.' }}</strong>
                </div>

                <div class="u22-target-chip">
                    <span>Gymdag</span>
                    <strong>{{ $player->settings?->kcal_training_day ?? 'n.v.t.' }}</strong>
                </div>

                <div class="u22-target-chip">
                    <span>Eiwit</span>
                    <strong>{{ $player->settings?->protein_target_min && $player->settings?->protein_target_max ? $player->settings->protein_target_min.'-'.$player->settings->protein_target_max.'g' : 'n.v.t.' }}</strong>
                </div>
            </div>
        </section>
    @endif

    <x-checkin-form :player="$player" :form="$form" :step="$step" :max-step="$maxStep" preview />
</div>
