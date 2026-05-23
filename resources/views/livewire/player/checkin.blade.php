<div class="mx-auto max-w-2xl space-y-6">
    <x-page-header title="Weekcheck" :description="$player->programName()" />

    @if ($saved)
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-5 text-emerald-950 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-100">
            <h2 class="text-lg font-semibold">Bedankt, je check-in is opgeslagen.</h2>
            <p class="mt-1 text-sm">Je kunt hem deze week nog aanpassen als er iets verandert.</p>
        </div>
    @endif

    <x-checkin-form :player="$player" :form="$form" :step="$step" :max-step="$maxStep" :autosaved="$autosaved" :autosaved-at="$autosavedAt" :step-error="$stepError" />
</div>
