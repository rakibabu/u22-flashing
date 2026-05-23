<div class="max-w-3xl space-y-6">
    <x-page-header title="Speler toevoegen" description="Maak een speler aan en genereer direct een invite-link." />

    <form wire:submit="save" class="u22-form-card space-y-5">
        <flux:input wire:model="name" label="Naam" />
        <flux:select wire:model="program_type" label="Programma">
            <flux:select.option value="conditioning">Conditie</flux:select.option>
            <flux:select.option value="muscle_gain">Bulk/kracht/spiermassa</flux:select.option>
            <flux:select.option value="maintenance">Onderhoud</flux:select.option>
        </flux:select>
        <div class="grid gap-4 sm:grid-cols-3">
            <flux:input wire:model="age" type="number" label="Leeftijd" />
            <flux:input wire:model="height_cm" type="number" label="Lengte cm" />
            <flux:input wire:model="start_weight_kg" type="number" step="0.1" label="Startgewicht" />
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <flux:input wire:model="target_weight_kg" type="number" step="0.1" label="Doelgewicht" />
            <flux:input wire:model="long_term_target_weight_kg" type="number" step="0.1" label="Lange termijn" />
        </div>
        <flux:textarea wire:model="notes" label="Notities" />
        <flux:input wire:model="training_program_pdf" type="file" label="Persoonlijk trainingsprogramma PDF" accept="application/pdf" />
        <flux:button type="submit" variant="primary">Opslaan</flux:button>
    </form>
</div>
