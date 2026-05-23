<div class="space-y-6">
    <x-page-header title="Voortgang" description="Je check-ins en testresultaten." />
    <div class="grid gap-3 md:grid-cols-2">
        @foreach ($player->checkins as $checkin)
            <article class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900" wire:key="progress-checkin-{{ $checkin->id }}">
                <h2 class="font-semibold">{{ $checkin->week_start_date->format('d-m-Y') }}</h2>
                <p class="mt-2 text-sm">Kracht {{ $checkin->strength_sessions }}, conditie {{ $checkin->conditioning_sessions }}, mobiliteit {{ $checkin->mobility_sessions }}</p>
                <p class="text-sm text-zinc-600 dark:text-zinc-300">Gewicht {{ $checkin->weight_kg ?? 'n.v.t.' }}, energie {{ $checkin->energy_score ?? 'n.v.t.' }}, slaap {{ $checkin->sleep_avg_hours ?? 'n.v.t.' }}</p>
            </article>
        @endforeach
    </div>
</div>
