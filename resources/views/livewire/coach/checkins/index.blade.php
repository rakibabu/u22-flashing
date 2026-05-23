<div class="space-y-6">
    <x-page-header title="Check-ins" description="Alle ingestuurde weekchecks." />
    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
            <thead class="bg-zinc-50 text-left text-xs uppercase text-zinc-500 dark:bg-zinc-950">
                <tr>
                    <th class="px-4 py-3">Week</th>
                    <th class="px-4 py-3">Speler</th>
                    <th class="px-4 py-3">Kracht</th>
                    <th class="px-4 py-3">Conditie</th>
                    <th class="px-4 py-3">Rustdag</th>
                    <th class="px-4 py-3">Slaap</th>
                    <th class="px-4 py-3">Pijn</th>
                    <th class="px-4 py-3">Actie</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @foreach ($checkins as $checkin)
                    <tr wire:key="checkin-row-{{ $checkin->id }}">
                        <td class="px-4 py-3">{{ $checkin->week_start_date->format('d-m-Y') }}</td>
                        <td class="px-4 py-3">{{ $checkin->player->name }}</td>
                        <td class="px-4 py-3">{{ $checkin->strength_sessions }}</td>
                        <td class="px-4 py-3">{{ $checkin->conditioning_sessions }}</td>
                        <td class="px-4 py-3">{{ $checkin->had_full_rest_day === null ? 'n.v.t.' : ($checkin->had_full_rest_day ? 'Ja' : 'Nee') }}</td>
                        <td class="px-4 py-3">{{ $checkin->sleep_avg_hours ?? 'n.v.t.' }}</td>
                        <td class="px-4 py-3">{{ $checkin->pain ? 'Ja' : 'Nee' }}</td>
                        <td class="px-4 py-3">
                            <flux:button size="sm" :href="route('coach.checkins.show', $checkin)" wire:navigate>Bekijk check-in</flux:button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
