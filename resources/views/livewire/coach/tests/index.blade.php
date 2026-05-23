<div class="space-y-6">
    <x-page-header title="Testresultaten" description="Voeg basistests toe en vergelijk later per speler." />

    <form wire:submit="save" class="u22-form-card grid gap-4 md:grid-cols-3">
        <flux:select wire:model="form.player_id" label="Speler">
            <flux:select.option value="">Kies speler</flux:select.option>
            @foreach ($players as $player)
                <flux:select.option value="{{ $player->id }}">{{ $player->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input wire:model="form.test_date" type="date" label="Datum" />
        <flux:input wire:model="form.body_weight_kg" type="number" step="0.1" label="Body weight" />
        <flux:input wire:model="form.sprint_20m_seconds" type="number" step="0.01" label="20m sprint" />
        <flux:input wire:model="form.five_min_run_meters" type="number" label="5-min run meters" />
        <flux:input wire:model="form.notes" label="Notities" />
        <div class="md:col-span-3"><flux:button type="submit" variant="primary">Opslaan</flux:button></div>
    </form>

    <div class="grid gap-3 md:grid-cols-2">
        @foreach ($results as $result)
            <article class="rounded-lg border border-zinc-200 bg-white p-4 text-sm dark:border-zinc-800 dark:bg-zinc-900" wire:key="test-{{ $result->id }}">
                <h2 class="font-semibold">{{ $result->player->name }} - {{ $result->test_date->format('d-m-Y') }}</h2>
                <p class="mt-2">20m: {{ $result->sprint_20m_seconds ?? 'n.v.t.' }} sec, 5-min: {{ $result->five_min_run_meters ?? 'n.v.t.' }}m, gewicht: {{ $result->body_weight_kg ?? 'n.v.t.' }}</p>
            </article>
        @endforeach
    </div>
</div>
