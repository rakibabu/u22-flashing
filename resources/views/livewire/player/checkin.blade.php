<div class="mx-auto max-w-2xl space-y-6">
    <x-page-header title="Weekcheck" :description="$player->programName()" />

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
            <p class="mt-1 text-sm">Elke zondag kun je via Weekcheck opnieuw invullen voor de week die net is geweest. Tot die tijd kun je deze weekcheck nog aanpassen als er iets verandert.</p>
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
        />
    @endif
</div>
