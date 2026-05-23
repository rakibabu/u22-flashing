<div class="space-y-6">
    <x-page-header title="Hoi {{ $player->name }}" description="Jouw U22 zomerprogramma voor deze week." />

    <div class="grid gap-3 sm:grid-cols-2">
        <a href="{{ route('player.program') }}" wire:navigate class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <p class="text-sm text-zinc-500">Jouw programma</p>
            <h2 class="mt-1 text-xl font-semibold">{{ $player->programName() }}</h2>
        </a>
        <a href="{{ route('player.checkin') }}" wire:navigate class="rounded-lg border border-orange-200 bg-orange-50 p-5 text-orange-950 dark:border-orange-900 dark:bg-orange-950 dark:text-orange-100">
            <p class="text-sm opacity-80">Weekcheck</p>
            <h2 class="mt-1 text-xl font-semibold">{{ $hasCheckinThisWeek ? 'Ingevuld' : 'Nog invullen' }}</h2>
        </a>
    </div>

    @if ($latestAdvice)
        <x-advice-card :note="$latestAdvice" />
    @endif

    <flux:button :href="route('player.checkin')" variant="primary" class="w-full sm:w-auto" wire:navigate>Weekcheck invullen</flux:button>
</div>
