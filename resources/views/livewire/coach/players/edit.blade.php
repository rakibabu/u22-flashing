<div class="max-w-3xl space-y-6">
    <x-page-header title="Speler bewerken" :description="$player->name" />
    <form wire:submit="save" class="u22-form-card space-y-5">
        <flux:input wire:model="form.name" label="Naam" />
        <flux:select wire:model="form.program_type" label="Programma">
            <flux:select.option value="conditioning">Conditie</flux:select.option>
            <flux:select.option value="muscle_gain">Bulk/kracht/spiermassa</flux:select.option>
            <flux:select.option value="maintenance">Onderhoud</flux:select.option>
            <flux:select.option value="guard_development">Guard development</flux:select.option>
        </flux:select>
        <p class="text-sm text-zinc-600 dark:text-zinc-400">
            Als je het programma wijzigt, worden de targets automatisch aangepast naar de standaardwaarden van het nieuwe programma.
        </p>
        <div class="grid gap-4 sm:grid-cols-3">
            <flux:input wire:model="form.age" type="number" label="Leeftijd" />
            <flux:input wire:model="form.height_cm" type="number" label="Lengte cm" />
            <flux:input wire:model="form.start_weight_kg" type="number" step="0.1" label="Startgewicht" />
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <flux:input wire:model="form.target_weight_kg" type="number" step="0.1" label="Doelgewicht" />
            <flux:input wire:model="form.long_term_target_weight_kg" type="number" step="0.1" label="Lange termijn" />
        </div>
        <flux:textarea wire:model="form.notes" label="Notities" />
        <flux:button type="submit" variant="primary">Opslaan</flux:button>
    </form>
</div>
