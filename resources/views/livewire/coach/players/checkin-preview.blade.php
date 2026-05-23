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
        <div class="u22-target-summary-head">
            <div>
                <p>Weekdoelen</p>
                <h2>Doelen deze week</h2>
            </div>
            <span>{{ $player->programName() }}</span>
        </div>

        <div class="u22-target-group">
            <div class="u22-target-group-head">
                <p>Training</p>
                <span>Aantal keer</span>
            </div>

            <div class="u22-target-grid">
                <div class="u22-target-chip">
                    <span>Kracht</span>
                    <strong>
                        {{ $player->settings?->strength_target_per_week ?: 'n.v.t.' }}
                        @if ($player->settings?->strength_target_per_week)
                            <small>x</small>
                        @endif
                    </strong>
                </div>

                <div class="u22-target-chip">
                    <span>Conditie</span>
                    <strong>
                        {{ $player->settings?->conditioning_target_per_week ?: 'n.v.t.' }}
                        @if ($player->settings?->conditioning_target_per_week)
                            <small>x</small>
                        @endif
                    </strong>
                </div>

                <div class="u22-target-chip">
                    <span>Mobiliteit</span>
                    <strong>
                        {{ $player->settings?->mobility_target_per_week ?: 'n.v.t.' }}
                        @if ($player->settings?->mobility_target_per_week)
                            <small>x</small>
                        @endif
                    </strong>
                </div>
            </div>
        </div>

        @if ($player->isMuscleGain())
            <div class="u22-target-divider"></div>

            <div class="u22-target-group">
                <div class="u22-target-group-head">
                    <p>Voeding</p>
                    <span>Spiermassa</span>
                </div>

                <div class="u22-target-grid">
                    <div class="u22-target-chip">
                        <span>Min kcal</span>
                        <strong>
                            {{ $player->settings?->kcal_minimum ?? 'n.v.t.' }}
                            @if ($player->settings?->kcal_minimum)
                                <small>kcal</small>
                            @endif
                        </strong>
                    </div>

                    <div class="u22-target-chip">
                        <span>Gymdag</span>
                        <strong>
                            {{ $player->settings?->kcal_training_day ?? 'n.v.t.' }}
                            @if ($player->settings?->kcal_training_day)
                                <small>kcal</small>
                            @endif
                        </strong>
                    </div>

                    <div class="u22-target-chip">
                        <span>Eiwit</span>
                        <strong class="u22-target-protein">
                            {{ $player->settings?->protein_target_min && $player->settings?->protein_target_max ? $player->settings->protein_target_min.'-'.$player->settings->protein_target_max : 'n.v.t.' }}
                            @if ($player->settings?->protein_target_min && $player->settings?->protein_target_max)
                                <small>g</small>
                            @endif
                        </strong>
                    </div>
                </div>
            </div>
        @endif
    </section>

    <x-checkin-form
        :player="$player"
        :form="$form"
        :step="$step"
        :max-step="$maxStep"
        :step-error="$stepError"
        :validation-scroll-field="$validationScrollField"
        :validation-scroll-tick="$validationScrollTick"
        preview
    />
</div>
