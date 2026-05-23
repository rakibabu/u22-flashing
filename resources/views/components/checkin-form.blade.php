@props([
    'player',
    'form',
    'preview' => false,
    'step' => 1,
    'maxStep' => 3,
    'autosaved' => false,
    'autosavedAt' => null,
    'stepError' => null,
])

@php
    $isBulk = $player->isMuscleGain();
    $isConditioning = $player->isConditioning();
    $settings = $player->settings;

    $steps = [
        ['number' => 1, 'label' => 'Training'],
        ['number' => 2, 'label' => 'Herstel'],
    ];

    if ($isBulk) {
        $steps[] = ['number' => 3, 'label' => 'Voeding'];
    } elseif ($isConditioning) {
        $steps[] = ['number' => 3, 'label' => 'Belasting'];
    }

    $steps[] = ['number' => $maxStep, 'label' => 'Afronden'];

    $reasonOptions = [
        'geen tijd' => 'Geen tijd',
        'vakantie' => 'Vakantie',
        'blessure' => 'Blessure',
        'motivatie' => 'Motivatie',
        'wist niet wat ik moest doen' => 'Wist niet wat ik moest doen',
        'gym niet beschikbaar' => 'Gym niet beschikbaar',
        'anders' => 'Anders',
    ];

    $countOptions = [
        0 => '0',
        1 => '1',
        2 => '2',
        3 => '3',
        4 => '4+',
    ];
    $scoreOptions = range(1, 10);
    $proteinDayOptions = range(0, 7);

    $strengthDone = (int) ($form['strength_sessions'] ?? 0);
    $strengthTarget = (int) ($settings?->strength_target_per_week ?? 0);
    $conditioningDone = (int) ($form['conditioning_sessions'] ?? 0);
    $conditioningTarget = (int) ($settings?->conditioning_target_per_week ?? 0);
    $mobilityDone = (int) ($form['mobility_sessions'] ?? 0);
    $mobilityTarget = (int) ($settings?->mobility_target_per_week ?? 0);
    $kcalAverage = $form['kcal_avg'] ?? null;
    $kcalMinimum = (int) ($settings?->kcal_minimum ?? 0);
    $proteinStatus = $form['protein_status'] ?? null;
    $proteinTargetDays = $form['protein_target_days'] ?? null;
    $proteinTrackingNeeded = $isBulk && (
        ($proteinTargetDays !== null && (int) $proteinTargetDays < 6)
        || in_array($proteinStatus, ['partial', 'no'], true)
    );
    $proteinStatusLabels = [
        'partial' => 'soms gehaald (3-5 dagen)',
        'no' => 'niet gehaald (0-2 dagen)',
    ];

    $underTargetReasons = [];
    $targetProgress = fn ($done, int $target) => $done !== null && $done !== '' ? "{$done}/{$target}" : "-/{$target}";

    if ($strengthDone < $strengthTarget) {
        $underTargetReasons[] = 'Kracht: '.$targetProgress($form['strength_sessions'] ?? null, $strengthTarget).' keer ingevuld.';
    }

    if ($conditioningDone < $conditioningTarget) {
        $underTargetReasons[] = ($isBulk ? 'Extra conditie' : 'Conditie').': '.$targetProgress($form['conditioning_sessions'] ?? null, $conditioningTarget).' keer ingevuld.';
    }

    if ($mobilityDone < $mobilityTarget) {
        $underTargetReasons[] = 'Preventie/mobiliteit: '.$targetProgress($form['mobility_sessions'] ?? null, $mobilityTarget).' keer ingevuld.';
    }

    if ($isBulk && $kcalAverage !== null && (int) $kcalAverage < $kcalMinimum) {
        $underTargetReasons[] = "Kcal: gemiddeld {$kcalAverage} per dag, minimum is {$kcalMinimum}.";
    }

    if ($proteinTrackingNeeded) {
        $underTargetReasons[] = $proteinTargetDays !== null
            ? "Eiwit: {$proteinTargetDays}/7 dagen gehaald."
            : 'Eiwit: '.($proteinStatusLabels[$proteinStatus] ?? 'niet volledig gehaald').'.';
    }

    $underTarget = $underTargetReasons !== [];
    $displayValue = fn ($value, string $suffix = '') => $value !== null && $value !== '' ? "{$value}{$suffix}" : '-';
    $summaryItems = [
        ['label' => 'Kracht', 'value' => $displayValue($form['strength_sessions'] ?? null, 'x')],
        ['label' => $isBulk ? 'Extra conditie' : 'Conditie', 'value' => $displayValue($form['conditioning_sessions'] ?? null, 'x')],
        ['label' => 'Mobiliteit', 'value' => $displayValue($form['mobility_sessions'] ?? null, 'x')],
        ['label' => 'Slaap', 'value' => $displayValue($form['sleep_avg_hours'] ?? null, 'u')],
        ['label' => 'Energie', 'value' => $displayValue($form['energy_score'] ?? null, '/10')],
        ['label' => 'Spierpijn', 'value' => $displayValue($form['soreness_score'] ?? null, '/10')],
        ['label' => 'Pijn', 'value' => ($form['pain'] ?? false) ? 'Ja' : 'Nee'],
    ];

    if ($isBulk) {
        $summaryItems[] = ['label' => 'Gewicht', 'value' => $displayValue($form['weight_kg'] ?? null, ' kg')];
        $summaryItems[] = ['label' => 'Kcal', 'value' => $displayValue($form['kcal_avg'] ?? null)];
        $summaryItems[] = ['label' => 'Eiwitdagen', 'value' => $displayValue($proteinTargetDays, '/7')];
    }

    if ($isConditioning) {
        $summaryItems[] = ['label' => 'Minuten', 'value' => $displayValue($form['total_training_minutes'] ?? null)];
        $summaryItems[] = ['label' => 'RPE', 'value' => $displayValue($form['highest_session_rpe'] ?? null, '/10')];
    }
@endphp

<form
    @if ($preview)
        x-data
        x-on:submit.prevent
    @else
        wire:submit="save"
    @endif
    class="u22-form-card u22-checkin-form space-y-6"
>
    <div class="space-y-4">
        <div class="flex items-center justify-between gap-3">
            <p class="text-sm font-semibold text-primary-800">Stap {{ $step }} van {{ $maxStep }}</p>
            <p class="rounded-full bg-primary-50 px-3 py-1 text-xs font-semibold text-primary-800">{{ $player->programName() }}</p>
        </div>

        <div class="h-2 overflow-hidden rounded-full bg-primary-50">
            <div class="h-full rounded-full bg-flash-orange transition-all duration-300" style="width: {{ round(($step / $maxStep) * 100) }}%"></div>
        </div>

        @unless ($preview)
            <div class="rounded-xl border border-primary-100 bg-white px-3 py-2 text-xs font-semibold text-primary-800">
                {{ $autosavedAt ? "Concept automatisch opgeslagen om {$autosavedAt}" : 'Concept wordt automatisch opgeslagen' }}
                <span class="block font-normal text-zinc-500">Definitief versturen doe je op de laatste stap.</span>
            </div>
        @endunless

        @if ($stepError)
            <div class="rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm font-semibold text-red-950">
                {{ $stepError }}
            </div>
        @endif

        <div @class([
            'grid gap-2',
            'grid-cols-3' => $maxStep === 3,
            'grid-cols-4' => $maxStep === 4,
        ])>
            @foreach ($steps as $item)
                <button
                    type="button"
                    wire:click="goToStep({{ $item['number'] }})"
                    aria-current="{{ $step === $item['number'] ? 'step' : 'false' }}"
                    @class([
                        'rounded-lg px-2 py-2 text-center text-xs font-semibold transition hover:-translate-y-0.5',
                        'bg-primary-600 text-white' => $step === $item['number'],
                        'bg-flash-orange/15 text-flash-orange' => $step > $item['number'],
                        'bg-primary-50 text-primary-800' => $step < $item['number'],
                    ])
                    wire:key="checkin-step-{{ $item['number'] }}"
                >
                    {{ $item['label'] }}
                </button>
            @endforeach
        </div>
    </div>

    @if ($step === 1)
        <section class="space-y-5">
            <div>
                <h2 class="font-display text-3xl leading-none text-primary-900">Training</h2>
                <p class="mt-1 text-sm text-zinc-600">
                    Target: {{ $settings?->strength_target_per_week ?? 0 }}x kracht,
                    {{ $settings?->conditioning_target_per_week ?? 0 }}x {{ $isBulk ? 'extra conditie' : 'conditie/pickup' }},
                    {{ $settings?->mobility_target_per_week ?? 0 }}x preventie.
                </p>
            </div>

            <div class="grid gap-4">
                <div class="u22-choice-block">
                    <div class="u22-choice-head">
                        <p>Aantal keer kracht</p>
                        <span>Doel {{ $settings?->strength_target_per_week ?? 0 }}x</span>
                    </div>
                    <div class="u22-choice-grid u22-choice-grid-count">
                        @foreach ($countOptions as $value => $label)
                            <label class="u22-choice-chip" wire:key="strength-count-{{ $value }}">
                                <input class="sr-only" type="radio" wire:model.live="form.strength_sessions" value="{{ $value }}">
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('form.strength_sessions') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="u22-choice-block">
                    <div class="u22-choice-head">
                        <p>{{ $isBulk ? 'Aantal keer extra conditie' : 'Aantal keer conditie' }}</p>
                        <span>Doel {{ $settings?->conditioning_target_per_week ?? 0 }}x</span>
                    </div>
                    <div class="u22-choice-grid u22-choice-grid-count">
                        @foreach ($countOptions as $value => $label)
                            <label class="u22-choice-chip" wire:key="conditioning-count-{{ $value }}">
                                <input class="sr-only" type="radio" wire:model.live="form.conditioning_sessions" value="{{ $value }}">
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('form.conditioning_sessions') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="u22-choice-block">
                    <div class="u22-choice-head">
                        <p>Aantal keer preventie/mobiliteit</p>
                        <span>Doel {{ $settings?->mobility_target_per_week ?? 0 }}x</span>
                    </div>
                    <div class="u22-choice-grid u22-choice-grid-count">
                        @foreach ($countOptions as $value => $label)
                            <label class="u22-choice-chip" wire:key="mobility-count-{{ $value }}">
                                <input class="sr-only" type="radio" wire:model.live="form.mobility_sessions" value="{{ $value }}">
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('form.mobility_sessions') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid gap-3">
                <label class="u22-check-card">
                    <input type="checkbox" wire:model.live="form.pickup_monday">
                    <span>
                        <strong>Pickup maandag</strong>
                        <small>{{ $settings?->pickup_monday_expected ? 'Staat in jouw planning' : 'Alleen invullen als je meedeed' }}</small>
                    </span>
                </label>

                @if ($isBulk)
                    <div class="rounded-xl border border-primary-100 bg-primary-50 p-4 text-sm text-primary-800">
                        Donderdagpickup staat niet in jouw spiermassa-plan.
                    </div>
                @else
                    <label class="u22-check-card">
                        <input type="checkbox" wire:model.live="form.pickup_thursday">
                        <span>
                            <strong>Pickup donderdag</strong>
                            <small>{{ $settings?->pickup_thursday_expected ? 'Staat in jouw planning' : 'Alleen invullen als je meedeed' }}</small>
                        </span>
                    </label>
                @endif

                <label class="u22-check-card">
                    <input type="checkbox" wire:model.live="form.had_full_rest_day">
                    <span>
                        <strong>Volledige rustdag gehad</strong>
                        <small>Een dag zonder training of pickup.</small>
                    </span>
                </label>
            </div>
        </section>
    @elseif ($step === 2)
        <section class="space-y-5">
            <div>
                <h2 class="font-display text-3xl leading-none text-primary-900">Herstel</h2>
                <p class="mt-1 text-sm text-zinc-600">Kort invullen hoe je lichaam deze week voelde.</p>
            </div>

            <div class="u22-number-grid u22-number-grid-single">
                <div class="u22-number-tile">
                    <label class="u22-number-label" for="checkin-sleep">Slaap gem. (uur)</label>
                    <input id="checkin-sleep" class="u22-number-input" wire:model.live.debounce.400ms="form.sleep_avg_hours" type="number" min="0" max="12" step="0.1" inputmode="decimal" placeholder="-">
                    <p class="u22-number-hint">Per nacht</p>
                    @error('form.sleep_avg_hours') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid gap-4">
                <div class="u22-choice-block">
                    <div class="u22-choice-head">
                        <p>Energie</p>
                        <span>1 laag, 10 top</span>
                    </div>
                    <div class="u22-choice-grid u22-choice-grid-score">
                        @foreach ($scoreOptions as $value)
                            <label class="u22-choice-chip u22-choice-chip-small" wire:key="energy-score-{{ $value }}">
                                <input class="sr-only" type="radio" wire:model.live="form.energy_score" value="{{ $value }}">
                                <span>{{ $value }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('form.energy_score') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="u22-choice-block">
                    <div class="u22-choice-head">
                        <p>Spierpijn/vermoeidheid</p>
                        <span>1 fris, 10 heel zwaar</span>
                    </div>
                    <div class="u22-choice-grid u22-choice-grid-score">
                        @foreach ($scoreOptions as $value)
                            <label class="u22-choice-chip u22-choice-chip-small" wire:key="soreness-score-{{ $value }}">
                                <input class="sr-only" type="radio" wire:model.live="form.soreness_score" value="{{ $value }}">
                                <span>{{ $value }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('form.soreness_score') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <label class="u22-check-card u22-check-card-danger">
                <input type="checkbox" wire:model.live="form.pain">
                <span>
                    <strong>Pijn of blessure</strong>
                    <small>Vink aan als iets niet goed voelt.</small>
                </span>
            </label>

            @if ($form['pain'])
                <div class="grid gap-4 rounded-2xl border border-red-100 bg-red-50 p-4">
                    <div>
                        <flux:input wire:model.live.debounce.500ms="form.pain_location" label="Waar zit de pijn?" :loading="false" />
                        @error('form.pain_location') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <flux:textarea wire:model.live.debounce.750ms="form.pain_notes" label="Korte toelichting pijn" rows="3" />
                </div>
            @endif
        </section>
    @elseif ($step === 3 && $isBulk)
        <section class="space-y-5">
            <div>
                <h2 class="font-display text-3xl leading-none text-primary-900">Voeding</h2>
                <p class="mt-1 text-sm text-zinc-600">Spiermassa-plan: gewicht, kcal, eiwit en eetlust.</p>
            </div>

            <div class="rounded-2xl border border-flash-orange/25 bg-flash-orange/10 p-4 text-sm text-primary-900">
                Minimum {{ $settings?->kcal_minimum ?? 3000 }} kcal. Gymdag {{ $settings?->kcal_training_day ?? 3400 }} kcal. Maandagpickup {{ $settings?->kcal_pickup_day ?? 3600 }} kcal. Eiwit {{ $settings?->protein_target_min ?? 120 }}-{{ $settings?->protein_target_max ?? 130 }}g.
            </div>

            <div class="u22-number-grid u22-number-grid-two">
                <div class="u22-number-tile">
                    <label class="u22-number-label" for="checkin-weight">Gewicht</label>
                    <input id="checkin-weight" class="u22-number-input" wire:model.live.debounce.400ms="form.weight_kg" type="number" min="40" max="160" step="0.1" inputmode="decimal" placeholder="-">
                    <p class="u22-number-hint">kg</p>
                    @error('form.weight_kg') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="u22-number-tile">
                    <label class="u22-number-label" for="checkin-kcal">Kcal gem.</label>
                    <input id="checkin-kcal" class="u22-number-input" wire:model.live.debounce.400ms="form.kcal_avg" type="number" min="1000" max="6000" inputmode="numeric" placeholder="-">
                    <p class="u22-number-hint">Per dag</p>
                    @error('form.kcal_avg') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="u22-choice-block">
                <div class="u22-choice-head">
                    <p>Eetlust</p>
                    <span>1 laag, 10 veel trek</span>
                </div>
                <div class="u22-choice-grid u22-choice-grid-score">
                    @foreach ($scoreOptions as $value)
                        <label class="u22-choice-chip u22-choice-chip-small" wire:key="appetite-score-{{ $value }}">
                            <input class="sr-only" type="radio" wire:model.live="form.appetite_score" value="{{ $value }}">
                            <span>{{ $value }}</span>
                        </label>
                    @endforeach
                </div>
                @error('form.appetite_score') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="u22-choice-block">
                <div class="u22-choice-head">
                    <p>Hoeveel dagen haalde je {{ $settings?->protein_target_min ?? 120 }}-{{ $settings?->protein_target_max ?? 130 }}g eiwit?</p>
                    <span>0-7 dagen</span>
                </div>
                <div class="u22-choice-grid u22-choice-grid-days">
                    @foreach ($proteinDayOptions as $value)
                        <label class="u22-choice-chip u22-choice-chip-small" wire:key="protein-days-{{ $value }}">
                            <input class="sr-only" type="radio" wire:model.live="form.protein_target_days" value="{{ $value }}">
                            <span>{{ $value }}</span>
                        </label>
                    @endforeach
                </div>
                @error('form.protein_target_days') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            @if ($proteinTrackingNeeded)
                <div class="rounded-2xl border border-flash-orange/25 bg-flash-orange/10 p-4">
                    <p class="font-semibold text-primary-900">Eiwit niet volledig gehaald</p>
                    <p class="mt-1 text-sm text-primary-800">Vul kort in wat je wel haalde, dan kan de coach gericht bijsturen.</p>

                    <div class="mt-4 grid gap-3">
                        <div class="u22-number-grid u22-number-grid-single">
                            <div class="u22-number-tile">
                                <label class="u22-number-label" for="checkin-protein-grams">Gem. eiwit</label>
                                <input id="checkin-protein-grams" class="u22-number-input" wire:model.live.debounce.400ms="form.protein_avg_grams" type="number" min="0" max="250" inputmode="numeric" placeholder="-">
                                <p class="u22-number-hint">Gram per dag</p>
                                @error('form.protein_avg_grams') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <flux:textarea wire:model.live.debounce.750ms="form.protein_notes" label="Wat lukte wel/niet met eiwit?" rows="3" />
                            @error('form.protein_notes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            @endif

            <div class="grid gap-3">
                <label class="u22-check-card">
                    <input type="checkbox" wire:model.live="form.used_mijn_eetmeter">
                    <span>
                        <strong>Mijn Eetmeter gebruikt</strong>
                        <small>Standaard app voor dit plan.</small>
                    </span>
                </label>

                <label class="u22-check-card">
                    <input type="checkbox" wire:model.live="form.used_yazio">
                    <span>
                        <strong>YAZIO gebruikt</strong>
                        <small>Backup als Mijn Eetmeter niet lukte.</small>
                    </span>
                </label>
            </div>
        </section>
    @elseif ($step === 3 && $isConditioning)
        <section class="space-y-5">
            <div>
                <h2 class="font-display text-3xl leading-none text-primary-900">Belasting</h2>
                <p class="mt-1 text-sm text-zinc-600">Alleen voor je conditieprogramma.</p>
            </div>

            <div class="u22-number-grid u22-number-grid-single">
                <div class="u22-number-tile">
                    <label class="u22-number-label" for="checkin-training-minutes">Totale trainingsminuten</label>
                    <input id="checkin-training-minutes" class="u22-number-input" wire:model.live.debounce.400ms="form.total_training_minutes" type="number" min="0" max="2000" inputmode="numeric" placeholder="-">
                    <p class="u22-number-hint">Deze week</p>
                    @error('form.total_training_minutes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="u22-choice-block">
                <div class="u22-choice-head">
                    <p>Zwaarste sessie</p>
                    <span>RPE 1-10</span>
                </div>
                <div class="u22-choice-grid u22-choice-grid-score">
                    @foreach ($scoreOptions as $value)
                        <label class="u22-choice-chip u22-choice-chip-small" wire:key="highest-rpe-{{ $value }}">
                            <input class="sr-only" type="radio" wire:model.live="form.highest_session_rpe" value="{{ $value }}">
                            <span>{{ $value }}</span>
                        </label>
                    @endforeach
                </div>
                @error('form.highest_session_rpe') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </section>
    @else
        <section class="space-y-5">
            <div>
                <h2 class="font-display text-3xl leading-none text-primary-900">Afronden</h2>
                <p class="mt-1 text-sm text-zinc-600">Alleen aanvullen wat belangrijk is voor de coach.</p>
            </div>

            <div class="rounded-2xl border border-primary-100 bg-white p-4">
                <p class="text-sm font-semibold text-primary-900">Controleer je week</p>
                <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-3">
                    @foreach ($summaryItems as $item)
                        <div class="rounded-xl border border-primary-100 bg-primary-50/40 px-3 py-2" wire:key="summary-{{ md5($item['label']) }}">
                            <span class="block text-[0.68rem] font-semibold uppercase leading-tight text-zinc-500">{{ $item['label'] }}</span>
                            <strong class="mt-1 block text-base text-primary-900">{{ $item['value'] }}</strong>
                        </div>
                    @endforeach
                </div>
            </div>

            @if ($underTarget)
                <div class="rounded-2xl border border-flash-orange/25 bg-flash-orange/10 p-4 text-primary-900">
                    <p class="font-semibold">Nog niet alles uit je weekdoel is gehaald</p>
                    <p class="mt-1 text-sm text-primary-800">De coach ziet hieronder precies waar het wringt. Kies daarna de belangrijkste reden.</p>
                    <ul class="mt-3 space-y-1 text-sm text-primary-900">
                        @foreach ($underTargetReasons as $reason)
                            <li class="flex gap-2" wire:key="under-target-{{ md5($reason) }}">
                                <span class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-flash-orange"></span>
                                <span>{{ $reason }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div>
                    <flux:select wire:model.live="form.missed_target_reason" label="Belangrijkste reden dat dit niet lukte">
                        <flux:select.option value="">Kies reden</flux:select.option>
                        @foreach ($reasonOptions as $value => $label)
                            <flux:select.option value="{{ $value }}" wire:key="reason-{{ $value }}">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    @error('form.missed_target_reason') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                @if (($form['missed_target_reason'] ?? null) === 'anders')
                    <div>
                        <flux:input wire:model.live.debounce.500ms="form.missed_target_reason_other" label="Andere reden" :loading="false" />
                        @error('form.missed_target_reason_other') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                @endif
            @else
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-950">
                    Je zit op schema met de belangrijkste targets.
                </div>
            @endif

            <flux:textarea wire:model.live.debounce.750ms="form.notes" rows="4" :label="$isBulk ? 'Extra opmerking voor de coach over training of eten' : 'Extra opmerking voor de coach'" />
            @error('form.notes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </section>
    @endif

    <div class="u22-checkin-actions">
        @if ($step > 1)
            <flux:button type="button" wire:click="previousStep" class="flex-1">Terug</flux:button>
        @endif

        @if ($step < $maxStep)
            <flux:button type="button" wire:click="nextStep" variant="primary" class="flex-1">Verder</flux:button>
        @elseif ($preview)
            <flux:button type="button" variant="primary" class="flex-1" disabled>Opslaan (preview)</flux:button>
        @else
            <flux:button type="submit" variant="primary" class="flex-1">Verstuur weekcheck</flux:button>
        @endif
    </div>
</form>
