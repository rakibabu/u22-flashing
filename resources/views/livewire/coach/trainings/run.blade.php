<div
    class="mx-auto max-w-2xl pb-44"
    x-data="trainingTimer({ startedAt: '{{ $blockRun->started_at?->toIso8601String() }}', pausedAt: '{{ $run->paused_at?->toIso8601String() }}', paused: @js($run->status->value === 'paused'), added: {{ $blockRun->added_duration_seconds }}, planned: {{ $block->planned_duration_minutes * 60 }}, totalStarted: '{{ $run->started_at->toIso8601String() }}', totalPaused: {{ $run->total_paused_seconds }} })"
    x-on:training-block-changed.window="reset($event.detail.timer)"
>
    <div class="rounded-b-xl bg-primary-800 p-4 text-white">
        <p class="text-sm text-white/70">{{ $training->title }} · Blok {{ $index + 1 }} van {{ $totalBlocks }}</p>

        <div class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <h1 class="font-display text-3xl leading-tight sm:text-4xl">{{ $block->title }}</h1>
            <span class="self-end text-2xl tabular-nums sm:self-auto" x-text="format(remaining())"></span>
        </div>

        <p class="mt-2 text-sm text-white/70">
            Totale tijd: <span x-text="format(totalElapsed())"></span> ·
            <span x-text="online ? 'Online' : 'Offline'" x-bind:class="online ? '' : 'text-flash-orange'"></span>
        </p>
    </div>

    <main class="space-y-4 p-4">
        <section>
            <p class="text-xs font-semibold uppercase text-flash-orange">Doel</p>
            <p class="mt-1 text-lg">{{ $block->exercise_snapshot['objective'] ?? $block->coach_notes ?? '—' }}</p>
        </section>

        <section>
            <p class="text-xs font-semibold uppercase text-flash-orange">Organisatie</p>
            <p class="mt-1 whitespace-pre-line">{{ $block->exercise_snapshot['organization'] ?? '—' }}</p>
        </section>

        <section>
            <p class="text-xs font-semibold uppercase text-flash-orange">Uitvoering</p>
            <p class="mt-1 whitespace-pre-line">{{ $block->exercise_snapshot['execution'] ?? $block->player_notes ?? '—' }}</p>
        </section>

        <section class="rounded-xl bg-primary-50 p-4 dark:bg-primary-800">
            <p class="font-semibold">Coaching points</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach($block->exercise_snapshot['coaching_points'] ?? [] as $point)
                    <li>{{ $point }}</li>
                @endforeach
            </ul>
        </section>

        @if($nextBlock)
            <p class="rounded-lg border p-3 text-sm text-zinc-600 dark:text-zinc-300">
                Hierna: <strong>{{ $nextBlock->title }}</strong> · {{ $nextBlock->planned_duration_minutes }} min
            </p>
        @endif
    </main>

    <div class="fixed inset-x-0 bottom-0 z-20 border-t border-zinc-200 bg-white p-3 pb-[calc(env(safe-area-inset-bottom)+0.75rem)] shadow-lg dark:border-zinc-800 dark:bg-zinc-900">
        <div class="mx-auto max-w-2xl space-y-2">
            <div class="grid grid-cols-3 gap-2">
                <flux:button size="sm" class="min-w-0 w-full" wire:click="previous">Vorige</flux:button>
                <flux:button size="sm" variant="primary" class="min-w-0 w-full" wire:click="{{ $run->status->value === 'paused' ? 'resume' : 'pause' }}">
                    {{ $run->status->value === 'paused' ? 'Hervat' : 'Pauze' }}
                </flux:button>
                <flux:button size="sm" class="min-w-0 w-full" wire:click="next">Volgende</flux:button>
            </div>

            <div x-data="{ additionalControlsOpen: false, offlineTraining: @js($offlineTraining) }" class="space-y-2">
                <flux:button
                    size="sm"
                    variant="subtle"
                    class="w-full"
                    x-on:click="additionalControlsOpen = ! additionalControlsOpen"
                    x-bind:aria-expanded="additionalControlsOpen"
                >
                    <span x-text="additionalControlsOpen ? 'Minder acties' : 'Meer acties'"></span>
                </flux:button>

                <div x-show="additionalControlsOpen" x-cloak class="space-y-2">
                    <div class="grid grid-cols-3 gap-2">
                        <flux:button size="sm" class="min-w-0 w-full" wire:click="addTwoMinutes">+2 min</flux:button>
                        <flux:button size="sm" class="min-w-0 w-full" wire:click="next(true)">Overslaan</flux:button>
                        <flux:button size="sm" variant="danger" class="min-w-0 w-full" wire:click="finish" wire:confirm="Training afronden?">Afronden</flux:button>
                    </div>

                    <div class="flex gap-2">
                        <flux:input wire:model="note" placeholder="Korte coachnotitie" class="min-w-0 flex-1" />
                        <flux:button size="sm" wire:click="saveNote">Opslaan</flux:button>
                    </div>

                    <flux:button size="sm" class="w-full" x-on:click="trainingOffline.saveTraining(offlineTraining)" x-show="!offlineSaved">
                        Maak offline beschikbaar
                    </flux:button>
                    <p x-show="offlineSaved" x-cloak class="text-center text-sm font-medium text-emerald-700 dark:text-emerald-300">
                        Offline klaar op dit toestel
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
