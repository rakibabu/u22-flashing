<div class="space-y-6">
    <x-page-header :title="$training->exists ? 'Training bewerken' : 'Nieuwe training'" description="Stel blokken samen en wijs per blok de uitvoerende coach toe.">
        <x-slot:actions>@if($training->exists)<flux:button :href="$whatsAppShareUrl" target="_blank" rel="noopener">Delen via WhatsApp</flux:button><flux:button :href="route('coach.trainings.run', $training)" variant="primary" wire:navigate>Uitvoeren</flux:button>@endif</x-slot:actions>
    </x-page-header>

    <form wire:submit="save" class="grid gap-4 rounded-xl border border-primary-100 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900 md:grid-cols-2">
        <flux:field><flux:label>Titel</flux:label><flux:input wire:model="title" /></flux:field>
        <flux:field><flux:label>Datum en starttijd</flux:label><flux:input type="datetime-local" wire:model="scheduledAt" /></flux:field>
        <flux:field><flux:label>Geplande duur (min.)</flux:label><flux:input type="number" min="1" wire:model="plannedDuration" /></flux:field>
        <flux:field><flux:label>Verwacht aantal spelers</flux:label><flux:input type="number" min="1" wire:model="expectedPlayers" /></flux:field>
        <flux:field><flux:label>Beschikbare baskets</flux:label><flux:input type="number" min="0" wire:model="availableBaskets" /></flux:field>
        <flux:field><flux:label>Thema</flux:label><flux:input wire:model="theme" /></flux:field>
        <flux:field class="md:col-span-2"><flux:label>Trainingsdoelen</flux:label><flux:textarea wire:model="goals" rows="2" /></flux:field>
        <flux:field class="md:col-span-2"><flux:label>Interne coachnotities</flux:label><flux:textarea wire:model="coachNotes" rows="2" /></flux:field>
        <div class="flex flex-wrap gap-2 md:col-span-2"><flux:button type="submit">Concept opslaan</flux:button><flux:button type="button" variant="primary" wire:click="save(true)">Publiceren</flux:button></div>
    </form>

    @if($training->exists)
        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)]">
            <aside class="space-y-3 rounded-xl border border-primary-100 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                <h2 class="font-display text-2xl text-primary-900 dark:text-white">Oefeningen toevoegen</h2>
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Zoeken" icon="magnifying-glass" />
                <flux:select wire:model.live="exerciseCategory"><flux:select.option value="">Alle categorieën</flux:select.option>@foreach($categories as $category)<flux:select.option :value="$category">{{ $category }}</flux:select.option>@endforeach</flux:select>
                @forelse($exercises as $exercise)<div wire:key="library-{{ $exercise->id }}" class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-700"><p class="font-semibold">{{ $exercise->name }}</p><p class="text-xs text-zinc-500">{{ $exercise->default_duration_minutes ?? 10 }} min · standaard: {{ $exercise->default_coach?->value ?? 'Raki' }}</p><flux:button size="sm" class="mt-2" wire:click="addExercise({{ $exercise->id }})">Toevoegen</flux:button></div>@empty<p class="text-sm text-zinc-500">De oude oefeningen zijn gearchiveerd. Voeg nieuwe oefeningen toe via Oefeningen.</p>@endforelse
            </aside>
            <section class="space-y-3">
                <div class="sticky top-2 z-10 rounded-xl border border-flash-orange/30 bg-flash-orange/10 p-4 text-primary-900 dark:text-orange-50"><strong>Gepland: {{ $plannedDuration }} min · Ingevuld: {{ $filledMinutes }} min</strong><span class="ml-2">{{ $filledMinutes > $plannedDuration ? 'Overschrijding: '.($filledMinutes-$plannedDuration).' min' : 'Nog beschikbaar: '.($plannedDuration-$filledMinutes).' min' }}</span></div>
                <flux:button wire:click="addText">Vrij tekstblok toevoegen</flux:button>
                @forelse($blocks as $block)
                    <article wire:key="block-{{ $block->id }}" class="rounded-xl border border-primary-100 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="grid gap-3 sm:grid-cols-[auto_minmax(0,1fr)_8rem]"><div class="flex gap-1"><flux:button size="sm" icon="chevron-up" wire:click="move({{ $block->id }}, 'up')" /><flux:button size="sm" icon="chevron-down" wire:click="move({{ $block->id }}, 'down')" /></div><flux:input :value="$block->title" wire:change="updateBlock({{ $block->id }}, 'title', $event.target.value)" /><flux:input type="number" min="1" :value="$block->planned_duration_minutes" wire:change="updateBlock({{ $block->id }}, 'planned_duration_minutes', $event.target.value)" /></div>
                        <div class="mt-3 max-w-xs"><flux:field><flux:label>Uitvoerende coach</flux:label><flux:select wire:change="updateBlock({{ $block->id }}, 'assigned_coach', $event.target.value)"><flux:select.option value="Raki" :selected="$block->assigned_coach->value === 'Raki'">Raki</flux:select.option><flux:select.option value="Tim" :selected="$block->assigned_coach->value === 'Tim'">Tim</flux:select.option><flux:select.option value="Jur" :selected="$block->assigned_coach->value === 'Jur'">Jur</flux:select.option></flux:select></flux:field></div>
                        @if($block->exercise_snapshot)<p class="mt-3 text-sm text-zinc-600 dark:text-zinc-300">{{ $block->exercise_snapshot['objective'] ?? $block->exercise_snapshot['execution'] ?? '' }}</p>@endif
                        <div class="mt-3"><flux:button size="sm" variant="danger" wire:click="removeBlock({{ $block->id }})">Verwijderen</flux:button></div>
                    </article>
                @empty <p class="rounded-xl border border-dashed p-6 text-sm text-zinc-500">Voeg een oefening of vrij tekstblok toe.</p>@endforelse
            </section>
        </div>
    @endif
</div>
