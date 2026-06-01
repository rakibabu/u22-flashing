<div class="mx-auto max-w-2xl space-y-6">
    <x-page-header title="Weekcheck" :description="$player->programName().' - '.$selectedWeekRange" />

    @if ($missedPreviousWeekCheckin)
        <div class="rounded-lg border border-red-200 bg-red-50 p-5 text-red-950 dark:border-red-900 dark:bg-red-950 dark:text-red-100">
            <h2 class="text-lg font-semibold">Weekcheck gemist</h2>
            <p class="mt-1 text-sm">De check-in voor {{ $previousWeekRange }} is gesloten. Vul deze week wel op tijd in en stuur je coach kort wat er vorige week speelde.</p>
        </div>
    @elseif ($previousWeekIsOpen && ! $hasPreviousWeekCheckin)
        <div class="rounded-lg border border-flash-orange/30 bg-flash-orange/10 p-5 text-primary-900 dark:border-flash-orange/40 dark:bg-flash-orange/15 dark:text-orange-50">
            <h2 class="text-lg font-semibold">Vorige week staat nog open</h2>
            <p class="mt-1 text-sm opacity-80">Je kunt de check-in voor {{ $previousWeekRange }} nog tot en met woensdag invullen.</p>
        </div>
    @endif

    <div class="rounded-lg border border-primary-800/10 bg-white p-4 shadow-sm dark:border-flash-orange/20 dark:bg-primary-800">
        <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-end">
            <flux:field>
                <flux:label>Week</flux:label>
                <flux:select wire:model.live="selectedWeekStartDate">
                    @foreach ($weekOptions as $option)
                        <flux:select.option value="{{ $option['value'] }}">{{ $option['label'] }} - {{ $option['description'] }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="selectedWeekStartDate" />
            </flux:field>
            <div class="text-sm text-zinc-600 dark:text-zinc-300">
                <p class="font-medium text-primary-900 dark:text-white">{{ $selectedWeekLabel }}</p>
                <p>{{ $selectedWeekRange }}</p>
            </div>
        </div>
    </div>

    @if ($saved)
        <div
            x-data
            x-init="
                Object.keys(window.localStorage)
                    .filter((key) => key.startsWith(@js('u22-checkin-draft:player:'.$player->id)))
                    .forEach((key) => window.localStorage.removeItem(key));
                window.scrollTo({ top: 0, behavior: 'smooth' });
            "
            class="rounded-lg border border-emerald-200 bg-emerald-50 p-5 text-emerald-950 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-100"
        >
            <h2 class="text-lg font-semibold">Bedankt, je check-in is opgeslagen.</h2>
            <p class="mt-1 text-sm">Je check-in voor {{ strtolower($selectedWeekLabel) }} is opgeslagen. Tot de deadline kun je deze weekcheck nog aanpassen als er iets verandert.</p>
        </div>
    @else
        <x-checkin-form
            :player="$player"
            :form="$form"
            :step="$step"
            :max-step="$maxStep"
            :autosaved="$autosaved"
            :autosaved-at="$autosavedAt"
            :step-error="$stepError"
            :validation-scroll-field="$validationScrollField"
            :validation-scroll-tick="$validationScrollTick"
            :saved="$saved"
            :selected-week-start-date="$selectedWeekStartDate"
            :week-label="$selectedWeekLabel"
        />
    @endif
</div>
