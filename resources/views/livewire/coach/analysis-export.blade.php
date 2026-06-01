<div class="space-y-6">
    <x-page-header title="Analyse export" description="Kopieer deze samenvatting naar ChatGPT voor persoonlijke adviezen.">
        <x-slot:actions>
            <flux:button :href="route('coach.analysis-export.csv', ['week' => $week])">CSV downloaden</flux:button>
        </x-slot:actions>
    </x-page-header>

    <section class="rounded-lg border border-primary-800/10 bg-white p-4 shadow-sm dark:border-flash-orange/20 dark:bg-primary-800">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-medium uppercase text-flash-orange">Adviesweek</p>
                <h2 class="mt-1 font-display text-3xl font-normal leading-none text-primary-900 dark:text-white">Week {{ $selectedWeekNumber }}</h2>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">{{ $selectedWeekRange }}</p>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ $players->count() }} speler(s) met een ingediende check-in in deze week.</p>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <flux:button size="sm" icon="chevron-left" wire:click="previousWeek">Vorige</flux:button>
                <flux:field class="sm:min-w-48">
                    <flux:label>Week</flux:label>
                    <flux:input type="week" wire:model.live="week" max="{{ $currentWeekValue }}" />
                </flux:field>
                <flux:button size="sm" icon="calendar-days" wire:click="currentWeek">Deze week</flux:button>
                <flux:button size="sm" icon="chevron-right" wire:click="nextWeek" :disabled="$isCurrentWeek">Volgende</flux:button>
            </div>
        </div>
    </section>

    <textarea readonly class="min-h-[520px] w-full rounded-lg border border-zinc-200 bg-white p-4 font-mono text-sm text-zinc-800 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-100">{{ $markdown }}</textarea>
</div>
